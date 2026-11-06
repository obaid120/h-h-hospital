<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['appointment_id', 'doctor_id', 'authored_by', 'version', 'last_save_key', 'last_save_hash', 'notes', 'advice', 'patient_name', 'patient_mr', 'patient_age', 'doctor_name', 'doctor_specialization', 'hospital_name', 'hospital_address', 'hospital_phone', 'hospital_footer', 'timezone', 'opd_date', 'token_number', 'draft_saved_at', 'finalized_at', 'finalized_by', 'completion_key'])]
#[Hidden(['last_save_key', 'last_save_hash', 'completion_key'])]
class Prescription extends Model
{
    protected function casts(): array
    {
        return ['version' => 'integer', 'patient_age' => 'integer', 'token_number' => 'integer', 'opd_date' => 'immutable_date', 'draft_saved_at' => 'immutable_datetime', 'finalized_at' => 'immutable_datetime'];
    }

    protected function opdDate(): Attribute
    {
        return Attribute::make(set: fn (string $date): string => CarbonImmutable::parse($date)->toDateString());
    }

    protected static function booted(): void
    {
        static::updating(function (Prescription $prescription): void {
            if ($prescription->getRawOriginal('finalized_at') !== null) {
                throw new LogicException('A finalized prescription cannot be edited.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Prescriptions must be retained.');
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('position');
    }
}
