<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['appointment_id', 'request_key', 'payload_hash', 'previous_status', 'reason', 'actor_id', 'actor_name', 'actor_role', 'cancelled_at', 'timezone', 'released_capacity'])]
#[Hidden(['request_key', 'payload_hash'])]
class AppointmentCancellation extends Model
{
    protected function casts(): array
    {
        return ['actor_role' => StaffRole::class, 'cancelled_at' => 'immutable_datetime', 'released_capacity' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Cancellation history cannot be edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Cancellation history must be retained.');
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
