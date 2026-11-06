<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['doctor_id', 'date'])]
class DoctorDay extends Model
{
    public const int CAPACITY = 50;

    protected $attributes = ['capacity' => self::CAPACITY, 'used_capacity' => 0, 'last_token' => 0];

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'capacity' => 'integer', 'used_capacity' => 'integer', 'last_token' => 'integer', 'current_appointment_id' => 'integer', 'queue_version' => 'integer'];
    }

    protected function date(): Attribute
    {
        return Attribute::make(set: fn (string $date): string => CarbonImmutable::parse($date)->toDateString());
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->capacity - $this->used_capacity);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function currentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'current_appointment_id');
    }
}
