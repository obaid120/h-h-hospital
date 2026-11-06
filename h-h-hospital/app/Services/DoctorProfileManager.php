<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorProfileManager
{
    /** @param array<string, mixed> $data */
    public function save(User $actor, ?Doctor $doctor, array $data, ?UploadedFile $photo = null): Doctor
    {
        $newPath = null;
        $oldPath = null;

        try {
            if ($photo) {
                $newPath = $photo->store('doctors', 'public');
                if ($newPath === false) {
                    throw ValidationException::withMessages(['photo' => __('doctors.validation.photo_failed')]);
                }
            }

            $saved = DB::transaction(function () use ($actor, $doctor, $data, $newPath, &$oldPath): Doctor {
                $authorizedActor = User::query()->lockForUpdate()->findOrFail($actor->id);
                abort_unless($authorizedActor->auth_version === $actor->auth_version, 403);
                Gate::forUser($authorizedActor)->authorize($doctor ? 'update' : 'create', $doctor ?? Doctor::class);

                if ($doctor) {
                    $profile = Doctor::query()->lockForUpdate()->findOrFail($doctor->id);
                } else {
                    if ($data['account_mode'] === 'new') {
                        $account = app(StaffAccountManager::class)->create($authorizedActor, Arr::only($data, ['name', 'email', 'password']) + ['role' => StaffRole::Doctor->value, 'is_active' => true]);
                    } else {
                        $account = User::query()->lockForUpdate()->findOrFail($data['user_id']);
                        if ($account->role !== StaffRole::Doctor || $account->doctor()->exists()) {
                            throw ValidationException::withMessages(['user_id' => __('doctors.validation.account')]);
                        }
                    }
                    $profile = new Doctor(['user_id' => $account->id]);
                }

                $before = $profile->exists ? $profile->only(['specialization', 'is_active', 'normal_fee_minor', 'priority_surcharge_minor']) : [];
                $oldPath = $profile->photo_path;
                $profile->fill(Arr::only($data, ['specialization', 'biography', 'is_active']));
                $profile->normal_fee_minor = $this->minorUnits($data['normal_fee']);
                $profile->priority_surcharge_minor = $this->minorUnits($data['priority_surcharge']);
                if ($newPath || ($data['remove_photo'] ?? false)) {
                    $profile->photo_path = $newPath;
                }
                $profile->save();
                $after = $profile->only(['specialization', 'is_active', 'normal_fee_minor', 'priority_surcharge_minor']);
                if ($before !== $after) {
                    app(ActivityRecorder::class)->record($authorizedActor, $before ? 'doctor.updated' : 'doctor.created', 'doctor', $profile->id, $before, $after, $profile->id);
                }

                return $profile;
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['user_id' => __('doctors.validation.account')]);
            }
            throw $exception;
        }

        if ($oldPath && $oldPath !== $saved->photo_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $saved;
    }

    private function minorUnits(string $amount): int
    {
        $parts = explode('.', $amount, 2);

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
