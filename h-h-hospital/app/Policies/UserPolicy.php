<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->is_active && ($actor->isActiveAdmin() || $actor->is($user));
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isActiveAdmin();
    }
}
