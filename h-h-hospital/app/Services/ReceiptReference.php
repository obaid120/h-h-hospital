<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReceiptReference
{
    public function generate(): string
    {
        return 'RCP-'.Str::ulid();
    }
}
