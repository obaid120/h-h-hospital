<?php

namespace App\Services;

use Illuminate\Support\Str;

class RefundReference
{
    public function generate(): string
    {
        return 'RFD-'.Str::ulid();
    }
}
