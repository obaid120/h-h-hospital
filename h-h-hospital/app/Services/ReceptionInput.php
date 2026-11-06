<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReceptionInput
{
    public static function rules(bool $walkIn = false): array
    {
        $rules = [
            'request_key' => ['required', 'uuid'],
            'patient_mode' => ['required', Rule::in(['existing', 'new'])],
            'patient_id' => ['exclude_unless:patient_mode,existing', 'required', 'integer', Rule::exists(Patient::class, 'id')],
            'name' => ['exclude_unless:patient_mode,new', 'required', 'string', 'max:100'],
            'phone' => ['exclude_unless:patient_mode,new', 'required', 'string', 'regex:/\A\+[1-9][0-9]{7,14}\z/'],
            'age_years' => ['required', 'integer', 'between:0,130'],
            'identity_confirmed' => ['required', 'accepted'],
            'cash_received' => ['required', 'accepted'],
        ];
        if ($walkIn) {
            $rules['is_priority'] = ['sometimes', 'boolean'];
            $rules['doctor_id'] = ['required', 'integer', Rule::exists(Doctor::class, 'id')];
            $rules['date'] = ['required', 'date_format:Y-m-d'];
        } else {
            $rules['is_priority'] = ['prohibited'];
            $rules['doctor_id'] = ['prohibited'];
            $rules['date'] = ['prohibited'];
        }
        foreach (['amount', 'total', 'total_minor', 'base_fee_minor', 'priority_charge_minor', 'normal_fee_minor', 'normal_fee', 'priority_surcharge_minor', 'currency', 'method', 'cashier_id', 'paid_at', 'receipt_number', 'source', 'status', 'priority', 'token_number', 'registered_by', 'patient_mr'] as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    public static function normalize(array $data): array
    {
        if (is_string($data['phone'] ?? null)) {
            $data['phone'] = PatientPhone::normalize($data['phone']) ?? $data['phone'];
        }
        if (is_string($data['name'] ?? null)) {
            $data['name'] = preg_replace('/\s+/u', ' ', trim($data['name']));
        }
        if (is_string($data['request_key'] ?? null)) {
            $data['request_key'] = Str::lower($data['request_key']);
        }

        return $data;
    }

    public static function fingerprint(array $data, string $flow, int $target): string
    {
        $identity = $data['patient_mode'] === 'existing'
            ? ['patient_id' => (int) $data['patient_id']]
            : ['name' => $data['name'], 'phone' => $data['phone']];

        $payload = ['flow' => $flow, 'target' => $target, 'date' => $data['date'] ?? null, 'mode' => $data['patient_mode'], 'identity' => $identity, 'age' => (int) $data['age_years']];
        if ($flow === 'walk_in' && ! empty($data['is_priority'])) {
            $payload['is_priority'] = true;
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
