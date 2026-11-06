<?php

namespace App\Services;

use Illuminate\Support\Str;

class CancellationRefundInput
{
    public static function rules(bool $refund = false): array
    {
        $rules = ['request_key' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:1000']];
        if ($refund) {
            $rules['amount'] = ['required', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'];
            $rules['cash_returned'] = ['required', 'accepted'];
        } else {
            $rules['cancellation_confirmed'] = ['required', 'accepted'];
            $rules['amount'] = ['prohibited'];
        }
        foreach (['appointment_id', 'payment_id', 'patient_id', 'doctor_id', 'doctor_day_id', 'actor_id', 'actor_name', 'actor_role', 'source', 'status', 'is_priority', 'receptionist_id', 'cashier_id', 'cancelled_by', 'refunded_by', 'confirmed_at', 'completed_at', 'cancelled_at', 'refunded_at', 'released_capacity', 'used_capacity', 'capacity', 'last_token', 'token_number', 'amount_minor', 'total_minor', 'currency', 'method', 'refund_reference', 'payload_hash'] as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    public static function normalize(array $data): array
    {
        if (is_string($data['request_key'] ?? null)) {
            $data['request_key'] = Str::lower(trim($data['request_key']));
        }
        if (is_string($data['reason'] ?? null)) {
            $data['reason'] = preg_replace('/\s+/u', ' ', trim($data['reason']));
        }
        if (is_string($data['amount'] ?? null)) {
            $data['amount'] = trim($data['amount']);
        }

        return $data;
    }

    public static function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function fingerprint(int $target, array $data, bool $refund = false): string
    {
        return hash('sha256', json_encode(['kind' => $refund ? 'refund' : 'cancellation', 'target' => $target, 'reason' => $data['reason'], 'amount_minor' => $refund ? self::minorUnits($data['amount']) : null], JSON_THROW_ON_ERROR));
    }
}
