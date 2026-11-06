<?php

namespace App\Services;

use App\Models\Appointment;

readonly class BookingResult
{
    public function __construct(public Appointment $appointment, public bool $replayed = false) {}
}
