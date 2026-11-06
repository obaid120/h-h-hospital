<?php

namespace App\Services;

use Illuminate\Support\Str;

class PrescriptionInput
{
    public static function rules(bool $completion = false): array
    {
        $rules = ['version' => ['required', 'integer', 'min:0', 'max:4294967294']];
        if ($completion) {
            $rules['completion_key'] = ['required', 'uuid'];
            $rules['completion_confirmed'] = ['required', 'accepted'];
        } else {
            $rules += [
                'request_key' => ['required', 'uuid'], 'notes' => ['nullable', 'string', 'max:10000'], 'advice' => ['nullable', 'string', 'max:5000'],
                'items' => ['present', 'array', 'list', 'max:30'], 'items.*' => ['array:medicine_name,dose,frequency,duration,instructions'],
                'items.*.medicine_name' => ['required', 'string', 'max:150'],
                'items.*.dose' => ['nullable', 'string', 'max:100'], 'items.*.frequency' => ['nullable', 'string', 'max:100'],
                'items.*.duration' => ['nullable', 'string', 'max:100'], 'items.*.instructions' => ['nullable', 'string', 'max:1000'],
            ];
        }
        foreach (['doctor_id', 'appointment_id', 'patient_id', 'patient_mr', 'patient_name', 'patient_age', 'authored_by', 'finalized_by', 'finalized_at', 'draft_saved_at', 'completed_at', 'status', 'token_number', 'doctor_day_id', 'last_save_hash', 'hospital_name', 'opd_date'] as $field) {
            $rules[$field] = ['prohibited'];
        }
        if ($completion) {
            foreach (['notes', 'advice', 'items', 'request_key'] as $field) {
                $rules[$field] = ['prohibited'];
            }
        }

        return $rules;
    }

    public static function normalize(array $data, bool $completion = false): array
    {
        foreach (['request_key', 'completion_key'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $data[$key] = Str::lower($data[$key]);
            }
        }
        if ($completion) {
            return $data;
        }
        foreach (['notes', 'advice'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $data[$key] = trim(str_replace(["\r\n", "\r"], "\n", $data[$key]));
            }
        }
        $data['items'] ??= [];
        if (is_array($data['items']) && array_is_list($data['items'])) {
            $rows = [];
            foreach ($data['items'] as $row) {
                if (! is_array($row)) {
                    $rows[] = $row;

                    continue;
                }
                $allowed = ['medicine_name', 'dose', 'frequency', 'duration', 'instructions'];
                foreach ($allowed as $field) {
                    if (is_string($row[$field] ?? null)) {
                        $row[$field] = trim($row[$field]);
                    }
                }
                if (array_diff(array_keys($row), $allowed) !== [] || collect($row)->contains(fn (mixed $value): bool => $value !== '' && $value !== null)) {
                    $rows[] = $row;
                }
            }
            $data['items'] = $rows;
        }

        return $data;
    }

    public static function fingerprint(int $appointmentId, array $data): string
    {
        $items = array_map(fn (array $row): array => array_replace(array_fill_keys(['medicine_name', 'dose', 'frequency', 'duration', 'instructions'], ''), array_map(fn (mixed $value): string => $value ?? '', $row)), $data['items']);

        return hash('sha256', json_encode(['appointment' => $appointmentId, 'version' => (int) $data['version'], 'notes' => $data['notes'] ?? '', 'advice' => $data['advice'] ?? '', 'items' => $items], JSON_THROW_ON_ERROR));
    }
}
