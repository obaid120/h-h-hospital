<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'name', 'phone', 'address', 'print_footer', 'version', 'updated_by'])]
class HospitalSetting extends Model
{
    protected function casts(): array
    {
        return ['version' => 'integer'];
    }
}
