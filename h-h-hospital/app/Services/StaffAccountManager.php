<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffAccountManager
{
    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): User
    {
        try {
            return DB::transaction(function () use ($actor, $data): User {
                $admins = $this->lockActiveAdmins();
                $authorizedActor = $this->authorizedActor($admins, $actor);
                Gate::forUser($authorizedActor)->authorize('create', User::class);

                return User::create(Arr::only($data, ['name', 'email', 'password', 'role', 'is_active']));
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('staff.validation.email_unique')]);
        }
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $user, array $data): User
    {
        try {
            return DB::transaction(function () use ($actor, $user, $data): User {
                $admins = $this->lockActiveAdmins();
                $authorizedActor = $this->authorizedActor($admins, $actor);
                $account = User::query()->lockForUpdate()->findOrFail($user->id);
                Gate::forUser($authorizedActor)->authorize('update', $account);

                $newRole = StaffRole::from($data['role']);
                $newActive = (bool) $data['is_active'];

                if ($newRole !== StaffRole::Doctor && $account->doctor()->exists()) {
                    throw ValidationException::withMessages(['role' => __('doctors.validation.linked_role')]);
                }

                if ($account->isActiveAdmin() && $admins->count() === 1 && (! $newActive || $newRole !== StaffRole::Admin)) {
                    throw ValidationException::withMessages(['is_active' => __('staff.validation.last_admin')]);
                }

                $attributes = Arr::only($data, ['name', 'email', 'role', 'is_active']);
                if (! empty($data['password'])) {
                    $attributes['password'] = $data['password'];
                }

                $account->fill($attributes);

                if ($account->isDirty(['email', 'password', 'role', 'is_active'])) {
                    $account->auth_version++;
                    $account->remember_token = Str::random(60);
                }

                $account->save();

                return $account;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('staff.validation.email_unique')]);
        }
    }

    /** @return Collection<int, User> */
    private function lockActiveAdmins(): Collection
    {
        return User::query()->where('role', StaffRole::Admin)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
    }

    /** @param Collection<int, User> $admins */
    private function authorizedActor(Collection $admins, User $actor): User
    {
        $authorizedActor = $admins->firstWhere('id', $actor->id);
        abort_unless($authorizedActor && $authorizedActor->auth_version === $actor->auth_version, 403);

        return $authorizedActor;
    }
}
