<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PatientRegistrar
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/\A\+[1-9][0-9]{7,14}\z/'],
            'age_years' => ['required', 'integer', 'between:0,130'],
            'registration_key' => ['required', 'uuid'],
            'identity_confirmed' => ['required', 'accepted'],
        ];
    }

    /** @param array<string, mixed> $data */
    public function register(User $actor, array $data): Patient
    {
        $data['phone'] = is_string($data['phone'] ?? null) ? PatientPhone::normalize($data['phone']) : null;
        $data = Validator::make($data, self::rules(), ['phone.regex' => __('patients.phone_error')])->validate();

        try {
            return DB::transaction(function () use ($actor, $data): Patient {
                $freshActor = User::query()->lockForUpdate()->findOrFail($actor->id);
                abort_unless($freshActor->auth_version === $actor->auth_version, 403);
                Gate::forUser($freshActor)->authorize('create', Patient::class);
                $patient = Patient::query()->where('registration_key', $data['registration_key'])->first();
                if ($patient) {
                    return $this->sameRegistration($patient, $freshActor, $data);
                }

                $patient = new Patient(['name' => $data['name'], 'phone' => $data['phone'], 'age_years' => $data['age_years']]);
                $patient->registration_key = $data['registration_key'];
                $patient->registered_by = $freshActor->id;
                $patient->registered_at = now();
                $patient->save();

                return $patient;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['registration_key' => __('patients.retry_changed')]);
        }
    }

    /** @param array<string, mixed> $data */
    private function sameRegistration(Patient $patient, User $actor, array $data): Patient
    {
        if ($patient->registered_by !== $actor->id || $patient->name !== $data['name'] || $patient->phone !== $data['phone'] || $patient->age_years !== (int) $data['age_years']) {
            throw ValidationException::withMessages(['registration_key' => __('patients.retry_changed')]);
        }

        return $patient;
    }
}
