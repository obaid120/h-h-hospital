<?php

namespace App\Services;

use App\Models\DoctorDay;
use Illuminate\Support\Facades\DB;
use LogicException;

class TokenAllocator
{
    public function next(DoctorDay $day): int
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Token allocation requires a reservation/payment transaction.');
        }
        $locked = DoctorDay::query()->lockForUpdate()->findOrFail($day->id);
        $locked->last_token++;
        $locked->save();

        return $locked->last_token;
    }
}
