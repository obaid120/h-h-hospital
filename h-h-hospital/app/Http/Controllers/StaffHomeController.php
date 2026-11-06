<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StaffHomeController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return to_route($request->user()->role->homeRoute());
    }

    public function index(Request $request): View
    {
        $summary = [];
        if ($request->user()->role === StaffRole::Admin) {
            $counts = User::query()->selectRaw('COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive')->first();
            $summary = ['total' => $counts->total, 'active' => $counts->active ?? 0, 'inactive' => $counts->inactive ?? 0];
        }

        return view('staff.home', ['staffUser' => $request->user(), 'summary' => $summary]);
    }

    public function profile(User $user): View
    {
        Gate::authorize('view', $user);

        return view('staff.profile', ['staffUser' => $user]);
    }
}
