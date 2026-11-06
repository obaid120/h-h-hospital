<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookingInput
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        $rules = [
            'doctor_id' => ['required', 'integer', Rule::exists(Doctor::class, 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/\A\+[1-9][0-9]{7,14}\z/'],
            'age_years' => ['required', 'integer', 'between:0,130'],
            'request_key' => ['required', 'uuid'],
        ];
        foreach (['source', 'status', 'priority', 'is_priority', 'fee', 'normal_fee', 'normal_fee_minor', 'priority_surcharge', 'priority_surcharge_minor', 'total', 'token_number', 'patient_id', 'verified_age', 'receptionist_id', 'confirmed_at', 'completed_at', 'cancelled_at', 'payment', 'paid'] as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
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
}
