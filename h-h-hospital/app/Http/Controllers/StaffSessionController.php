<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class StaffSessionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json(['user_id' => $request->user()->id, 'csrf_token' => csrf_token()], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function create(): View
    {
        return view('staff.auth.login');
    }

    public function store(StaffLoginRequest $request): RedirectResponse
    {
        if (RateLimiter::tooManyAttempts($request->throttleKey(), 5) || RateLimiter::tooManyAttempts($request->ipThrottleKey(), 25)) {
            $seconds = max(RateLimiter::availableIn($request->throttleKey()), RateLimiter::availableIn($request->ipThrottleKey()));

            throw ValidationException::withMessages(['email' => __('staff.auth.throttled', ['seconds' => $seconds])]);
        }

        if (! Auth::attempt([...$request->safe()->only(['email', 'password']), 'is_active' => true])) {
            RateLimiter::hit($request->throttleKey(), 60);
            RateLimiter::hit($request->ipThrottleKey(), 60);

            throw ValidationException::withMessages(['email' => __('staff.auth.failed')]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();
        $request->session()->put('staff_auth_version', $request->user()->auth_version);
        $request->session()->forget('url.intended');

        return to_route($request->user()->role->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('staff.login')->with('status', __('staff.auth.signed_out'));
    }
}
