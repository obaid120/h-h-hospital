<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['payment_id', 'refund_reference', 'request_key', 'payload_hash', 'amount_minor', 'currency', 'method', 'reason', 'actor_id', 'actor_name', 'actor_role', 'refunded_at', 'timezone'])]
#[Hidden(['request_key', 'payload_hash'])]
class PaymentRefund extends Model
{
    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'actor_role' => StaffRole::class, 'refunded_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Original refund records cannot be edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Refund records must be retained.');
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
