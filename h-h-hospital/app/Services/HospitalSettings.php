<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\HospitalSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HospitalSettings
{
    private ?array $values = null;

    public static function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:40', 'regex:/\A[+0-9() .-]+\z/'], 'address' => ['nullable', 'string', 'max:500'], 'print_footer' => ['nullable', 'string', 'max:300'], 'version' => ['required', 'integer', 'min:0'], 'timezone' => ['prohibited'], 'currency' => ['prohibited'], 'updated_by' => ['prohibited'], 'id' => ['prohibited']];
    }

    public static function normalize(array $input): array
    {
        foreach (['name', 'phone', 'address', 'print_footer'] as $field) {
            if (is_string($input[$field] ?? null)) {
                $input[$field] = trim($input[$field]);
                if ($field !== 'name' && $input[$field] === '') {
                    $input[$field] = null;
                }
            }
        }

        return $input;
    }

    /** @return array{name: string, phone: ?string, address: ?string, print_footer: ?string, version: int} */
    public function current(): array
    {
        $defaults = [
            'name' => config('app.name'), 'phone' => config('hospital.contact.phone'), 'address' => config('hospital.contact.address'), 'print_footer' => null, 'version' => 0,
        ];
        if (request()->routeIs('design.*')) {
            return $defaults;
        }

        return $this->values ??= HospitalSetting::find(1)?->only(['name', 'phone', 'address', 'print_footer', 'version']) ?? $defaults;
    }

    public function save(User $actor, array $input): HospitalSetting
    {
        $data = Validator::make(self::normalize($input), self::rules())->validate();
        $saved = DB::transaction(function () use ($actor, $data): HospitalSetting {
            $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
            abort_unless($fresh->is_active && $fresh->role === StaffRole::Admin && $fresh->auth_version === $actor->auth_version, 403);
            $settings = HospitalSetting::query()->lockForUpdate()->find(1);
            if (! $settings) {
                DB::table('hospital_settings')->insertOrIgnore(['id' => 1, 'name' => config('app.name'), 'phone' => config('hospital.contact.phone'), 'address' => config('hospital.contact.address'), 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $settings = HospitalSetting::query()->lockForUpdate()->findOrFail(1);
            }
            $before = $settings->only(['name', 'phone', 'address', 'print_footer']);
            $after = ['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null, 'print_footer' => $data['print_footer'] ?? null];
            if ($before === $after) {
                return $settings;
            }
            if ($settings->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => __('admin.settings_conflict')]);
            }
            $settings->fill($after);
            $settings->version++;
            $settings->updated_by = $fresh->id;
            $settings->save();
            app(ActivityRecorder::class)->record($fresh, 'hospital.updated', 'hospital', 1, $before, $after);

            return $settings;
        }, attempts: 3);
        $this->values = null;

        return $saved;
    }
}
