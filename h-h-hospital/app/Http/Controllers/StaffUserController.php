<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Http\Requests\StaffUserRequest;
use App\Models\User;
use App\Services\StaffAccountManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(StaffRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return view('staff.users.index', compact('users', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('staff.users.form', ['account' => new User(['role' => StaffRole::Receptionist, 'is_active' => true])]);
    }

    public function store(StaffUserRequest $request, StaffAccountManager $manager): RedirectResponse
    {
        $account = $manager->create($request->user(), $request->validated());

        return to_route('staff.admin.users.edit', $account)->with('status', __('staff.users.created'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('staff.users.form', ['account' => $user]);
    }

    public function update(StaffUserRequest $request, User $user, StaffAccountManager $manager): RedirectResponse
    {
        $account = $manager->update($request->user(), $user, $request->validated());

        if ($account->is($request->user()) && $account->auth_version !== (int) $request->session()->get('staff_auth_version')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('staff.login')->with('status', __('staff.users.sign_in_again'));
        }

        return to_route('staff.admin.users.edit', $account)->with('status', __('staff.users.updated'));
    }
}
