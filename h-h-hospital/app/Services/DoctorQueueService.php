<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoctorQueueService
{
    public static function rules(): array
    {
        return [
            'queue_date' => ['required', 'date_format:Y-m-d'], 'queue_version' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'doctor_id' => ['prohibited'], 'appointment_id' => ['prohibited'], 'date' => ['prohibited'],
            'status' => ['prohibited'], 'token_number' => ['prohibited'], 'is_priority' => ['prohibited'],
        ];
    }

    /** @return array{doctor: ?Doctor, day: ?DoctorDay, current: ?Appointment, waiting: Collection, date: string, version: int} */
    public function snapshot(User $actor): array
    {
        return DB::transaction(function () use ($actor): array {
            [$doctor, $day, $date] = $this->context($actor);
            $current = $day ? $this->current($day) : null;
            $waiting = $day ? $this->waiting($day)->with('patient')->get() : (new Appointment)->newCollection();

            return ['doctor' => $doctor, 'day' => $day, 'current' => $current?->load('patient'), 'waiting' => $waiting, 'date' => $date, 'version' => $day?->queue_version ?? 0];
        }, attempts: 3);
    }

    public function callNext(User $actor, array $input): ?Appointment
    {
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $data): ?Appointment {
            [$doctor, $day, $date] = $this->context($actor);
            abort_unless($doctor, 403);
            if ($date !== $data['queue_date']) {
                $this->reject('date_changed');
            }
            if (! $day) {
                return null;
            }
            $current = $this->current($day);
            if ($current) {
                return $current;
            }
            if ($day->queue_version !== (int) $data['queue_version']) {
                $this->reject('stale');
            }
            $next = $this->waiting($day)->lockForUpdate()->first();
            if (! $next) {
                return null;
            }
            $next->consultation_started_at = now();
            $next->save();
            $day->current_appointment_id = $next->id;
            $day->queue_version++;
            $day->save();
            app(ActivityRecorder::class)->record($doctor->user, 'appointment.started', 'appointment', $next->id, [], ['token_number' => $next->token_number], $doctor->id, 'started:'.$next->id);

            return $next;
        }, attempts: 3);
    }

    /** @return array{?Doctor, ?DoctorDay, string} */
    private function context(User $actor): array
    {
        $doctor = Doctor::query()->where('user_id', $actor->id)->lockForUpdate()->first();
        $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
        abort_unless($fresh->is_active && $fresh->role === StaffRole::Doctor && $fresh->auth_version === $actor->auth_version, 403);
        $date = now(config('hospital.timezone'))->toDateString();
        $doctor?->setRelation('user', $fresh);
        $day = $doctor ? DoctorDay::query()->where('doctor_id', $doctor->id)->where('date', $date)->lockForUpdate()->first() : null;

        return [$doctor, $day, $date];
    }

    private function current(DoctorDay $day): ?Appointment
    {
        $started = $day->appointments()->where('status', AppointmentStatus::Confirmed)->whereNotNull('consultation_started_at')->lockForUpdate()->get();
        if ($started->count() > 1 || ($started->first()?->id !== $day->current_appointment_id)) {
            $this->reject('needs_review');
        }

        return $started->first();
    }

    private function waiting(DoctorDay $day): Builder
    {
        return Appointment::query()->where('doctor_day_id', $day->id)->where('status', AppointmentStatus::Confirmed)
            ->whereNull('consultation_started_at')->whereNotNull('patient_id')->whereNotNull('verified_age')->whereNotNull('token_number')
            ->orderByDesc('is_priority')->orderBy('token_number')->orderBy('id');
    }

    private function reject(string $reason): never
    {
        throw ValidationException::withMessages(['queue' => __('queue.errors.'.$reason)]);
    }
}
