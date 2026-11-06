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

#[Fillable(['appointment_id', 'receipt_number', 'request_key', 'request_payload_hash', 'base_fee_minor', 'priority_charge_minor', 'total_minor', 'is_priority', 'currency', 'method', 'cashier_id', 'cashier_name', 'paid_at', 'timezone', 'patient_name', 'patient_mr', 'patient_phone', 'patient_age', 'doctor_name', 'opd_date', 'token_number', 'hospital_name', 'hospital_address', 'hospital_phone', 'hospital_footer'])]
#[Hidden(['request_key', 'request_payload_hash'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return ['base_fee_minor' => 'integer', 'priority_charge_minor' => 'integer', 'total_minor' => 'integer', 'is_priority' => 'boolean', 'patient_age' => 'integer', 'token_number' => 'integer', 'paid_at' => 'immutable_datetime', 'opd_date' => 'immutable_date'];
    }

    protected function opdDate(): Attribute
    {
        return Attribute::make(set: fn (string $date): string => CarbonImmutable::parse($date)->toDateString());
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Original payment records cannot be edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Original payment records cannot be deleted.');
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function amount(int $minor): string
    {
        return $this->currency.' '.number_format($minor / 100, 2);
    }
}
