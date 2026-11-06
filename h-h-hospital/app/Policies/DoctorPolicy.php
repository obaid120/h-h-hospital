<?php

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && in_array($actor->role, [StaffRole::Admin, StaffRole::Receptionist], true);
    }

    public function view(User $actor, Doctor $doctor): bool
    {
        return $this->viewAny($actor) || ($actor->is_active && $actor->role === StaffRole::Doctor && $actor->id === $doctor->user_id);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveAdmin();
    }

    public function update(User $actor, Doctor $doctor): bool
    {
        return $actor->isActiveAdmin();
    }
}
