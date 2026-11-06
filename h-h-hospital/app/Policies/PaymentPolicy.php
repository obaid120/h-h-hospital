<?php

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function refund(User $actor, Payment $payment): bool
    {
        return $actor->is_active && $actor->role === StaffRole::Admin;
    }

    public function view(User $actor, Payment $payment): bool
    {
        return $actor->is_active && in_array($actor->role, [StaffRole::Admin, StaffRole::Receptionist], true);
    }
}
