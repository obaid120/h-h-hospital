<?php

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Patient $patient): bool
    {
        return $actor->is_active && Patient::query()->visibleTo($actor)->whereKey($patient->id)->exists();
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && in_array($actor->role, [StaffRole::Admin, StaffRole::Receptionist], true);
    }
}
