<?php

namespace App\Services;

use RuntimeException;

class BookingRejected extends RuntimeException
{
    /** @param array<string, mixed>|null $availability */
    public function __construct(public readonly string $reason, public readonly ?array $availability = null)
    {
        parent::__construct(__('booking.errors.'.$reason));
    }
}
