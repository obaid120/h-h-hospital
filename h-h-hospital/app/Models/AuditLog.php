<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable(['event_key', 'action', 'record_type', 'record_id', 'doctor_id', 'actor_id', 'actor_name', 'actor_role', 'before_values', 'after_values', 'currency', 'occurred_at', 'timezone', 'historical'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'occurred_at' => 'immutable_datetime', 'historical' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Activity records cannot be edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Activity records must be retained.');
        });
    }

    public function targetUrl(): ?string
    {
        return match ($this->record_type) {
            'doctor' => route('staff.doctors.show', $this->record_id),
            'appointment' => route('staff.appointments.show', $this->record_id),
            'payment' => route('staff.receipts.show', $this->record_id),
            'hospital' => route('staff.admin.settings.edit'),
            default => null,
        };
    }

    public function displayValue(string $field, mixed $value): string
    {
        if ($field === 'schedule_override') {
            return __('doctors.modes.'.($value ?? 'weekly'));
        }
        if ($value === null || $value === '') {
            return '—';
        }
        if (str_ends_with($field, '_minor')) {
            return $this->currency.' '.number_format((int) $value / 100, 2);
        }
        if (is_bool($value)) {
            return $value ? __('admin.yes') : __('admin.no');
        }
        if ($field === 'schedule') {
            return collect($value)->map(fn (array $hours, int $day): string => __('doctors.weekdays.'.$day).' '.$hours['opens_at'].'–'.$hours['closes_at'])->implode('; ') ?: '—';
        }
        if ($field === 'status') {
            return __('ui.status.'.$value);
        }
        if ($field === 'source') {
            return __('patients.sources.'.$value);
        }

        return (string) $value;
    }
}
