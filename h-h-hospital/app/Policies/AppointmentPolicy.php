<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function cancel(User $actor, Appointment $appointment): bool
    {
        return $this->viewAny($actor);
    }

    public function viewPrescription(User $actor, Appointment $appointment): bool
    {
        return $actor->is_active && in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true)
            && ($actor->role === StaffRole::Admin || ($actor->role === StaffRole::Doctor && $appointment->doctorDay->doctor->user_id === $actor->id));
    }

    public function writePrescription(User $actor, Appointment $appointment): bool
    {
        return $actor->role === StaffRole::Doctor && $this->viewPrescription($actor, $appointment) && $appointment->status === AppointmentStatus::Confirmed;
    }

    public function completeOpd(User $actor, Appointment $appointment): bool
    {
        return $actor->role === StaffRole::Doctor && $this->viewPrescription($actor, $appointment);
    }

    public function viewQueue(User $actor): bool
    {
        return $actor->is_active && $actor->role === StaffRole::Doctor;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->is_active && in_array($actor->role, [StaffRole::Admin, StaffRole::Receptionist], true);
    }

    public function view(User $actor, Appointment $appointment): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && $actor->role === StaffRole::Receptionist;
    }

    public function confirm(User $actor, Appointment $appointment): bool
    {
        return $this->create($actor);
    }
}
