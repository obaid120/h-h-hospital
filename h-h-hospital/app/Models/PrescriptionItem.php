<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['position', 'medicine_name', 'dose', 'frequency', 'duration', 'instructions'])]
class PrescriptionItem extends Model
{
    protected static function booted(): void
    {
        $protectFinalized = function (PrescriptionItem $item): void {
            if ($item->prescription()->whereNotNull('finalized_at')->exists()) {
                throw new LogicException('Finalized medicine lines cannot be changed.');
            }
        };
        static::saving($protectFinalized);
        static::deleting($protectFinalized);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
