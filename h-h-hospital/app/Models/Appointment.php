<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['public_reference', 'request_key', 'doctor_day_id', 'patient_id', 'submitted_name', 'submitted_phone', 'submitted_age', 'verified_age', 'source', 'status', 'is_priority', 'token_number', 'normal_fee_minor', 'priority_surcharge_minor', 'receptionist_id', 'confirmed_at', 'consultation_started_at', 'completed_at', 'cancelled_at'])]
#[Hidden(['request_key', 'booking_owner_hash', 'request_payload_hash'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'is_priority' => false, 'priority_surcharge_minor' => 0];

    protected function casts(): array
    {
        return [
            'source' => AppointmentSource::class, 'status' => AppointmentStatus::class, 'is_priority' => 'boolean',
            'submitted_age' => 'integer', 'verified_age' => 'integer', 'token_number' => 'integer',
            'normal_fee_minor' => 'integer', 'priority_surcharge_minor' => 'integer',
            'confirmed_at' => 'immutable_datetime', 'consultation_started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function doctorDay(): BelongsTo
    {
        return $this->belongsTo(DoctorDay::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(AppointmentCancellation::class);
    }

    public function scopeVisibleTo(Builder $query, User $actor): void
    {
        if (! $actor->is_active) {
            $query->whereRaw('1 = 0');
        } elseif ($actor->role === StaffRole::Doctor) {
            $query->whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Completed])
                ->whereHas('doctorDay.doctor', fn (Builder $doctors): Builder => $doctors->where('user_id', $actor->id));
        }
    }
}
