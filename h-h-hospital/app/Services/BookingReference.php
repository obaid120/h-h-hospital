<?php

namespace App\Services;

use Illuminate\Support\Str;

class BookingReference
{
    public function generate(): string
    {
        return 'OPD-'.Str::ulid();
    }
}
