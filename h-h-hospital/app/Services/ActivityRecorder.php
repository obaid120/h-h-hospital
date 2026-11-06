<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;

class ActivityRecorder
{
    public function record(User $actor, string $action, string $type, int $id, array $before, array $after, ?int $doctorId = null, ?string $key = null, ?string $currency = null): AuditLog
    {
        return AuditLog::create([
            'event_key' => $key ?? (string) Str::uuid(), 'action' => $action, 'record_type' => $type, 'record_id' => $id,
            'doctor_id' => $doctorId, 'actor_id' => $actor->id, 'actor_name' => $actor->name, 'actor_role' => $actor->role->value,
            'before_values' => $before, 'after_values' => $after, 'currency' => $currency ?? config('hospital.currency'),
            'occurred_at' => now(), 'timezone' => config('hospital.timezone'), 'historical' => false,
        ]);
    }
}
