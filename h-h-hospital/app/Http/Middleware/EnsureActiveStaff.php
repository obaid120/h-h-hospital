<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->fresh();

        if (! $user || ! $user->is_active || (int) $request->session()->get('staff_auth_version', 0) !== $user->auth_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => __('staff.auth.session_ended')], 401);
            }

            return to_route('staff.login')->with('status', __('staff.auth.session_ended'));
        }

        Auth::guard('web')->setUser($user);

        return $next($request);
    }
}
