<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\AppointmentCancellation;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CancellationService
{
    public static function eligible(Appointment $visit, DoctorDay $day): bool
    {
        return in_array($visit->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)
            && $visit->consultation_started_at === null && $visit->completed_at === null && $day->current_appointment_id !== $visit->id;
    }

    public function cancel(User $actor, Appointment $appointment, array $input): AppointmentCancellation
    {
        $data = Validator::make(CancellationRefundInput::normalize($input), CancellationRefundInput::rules())->validate();
        $hash = CancellationRefundInput::fingerprint($appointment->id, $data);
        $anchor = Appointment::query()->with('doctorDay')->findOrFail($appointment->id);

        try {
            return DB::transaction(function () use ($actor, $data, $hash, $anchor): AppointmentCancellation {
                $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
                abort_unless($fresh->is_active && in_array($fresh->role, [StaffRole::Admin, StaffRole::Receptionist], true) && $fresh->auth_version === $actor->auth_version, 403);
                Doctor::query()->lockForUpdate()->findOrFail($anchor->doctorDay->doctor_id);
                $day = DoctorDay::query()->lockForUpdate()->findOrFail($anchor->doctor_day_id);
                $visit = Appointment::query()->lockForUpdate()->findOrFail($anchor->id);
                abort_unless($visit->doctor_day_id === $day->id, 403);
                $keyOwner = AppointmentCancellation::query()->where('request_key', $data['request_key'])->first();
                if ($keyOwner && ! hash_equals($keyOwner->payload_hash, $hash)) {
                    $this->reject('key_conflict');
                }
                $existing = $keyOwner ?? AppointmentCancellation::query()->where('appointment_id', $visit->id)->first();
                if ($existing) {
                    if ($visit->status !== AppointmentStatus::Cancelled || ! $visit->cancelled_at) {
                        $this->reject('needs_review');
                    }

                    return $existing;
                }
                if (! self::eligible($visit, $day) || $visit->prescription()->exists()) {
                    $this->reject('not_eligible');
                }
                $released = DoctorDay::query()->whereKey($day->id)->where('used_capacity', '>', 0)->decrement('used_capacity');
                if ($released !== 1) {
                    $this->reject('needs_review');
                }
                $previous = $visit->status->value;
                $visit->status = AppointmentStatus::Cancelled;
                $visit->cancelled_at = now();
                $visit->save();

                $cancellation = AppointmentCancellation::create([
                    'appointment_id' => $visit->id, 'request_key' => $data['request_key'], 'payload_hash' => $hash,
                    'previous_status' => $previous, 'reason' => $data['reason'], 'actor_id' => $fresh->id,
                    'actor_name' => $fresh->name, 'actor_role' => $fresh->role, 'cancelled_at' => $visit->cancelled_at,
                    'timezone' => config('hospital.timezone'), 'released_capacity' => 1,
                ]);
                app(ActivityRecorder::class)->record($fresh, 'appointment.cancelled', 'appointment', $visit->id, ['status' => $previous], ['status' => 'cancelled', 'reason' => $cancellation->reason, 'released_capacity' => 1], $day->doctor_id, 'cancellation:'.$cancellation->id);

                return $cancellation;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            $this->reject('key_conflict');
        }
    }

    private function reject(string $reason): never
    {
        throw ValidationException::withMessages(['cancellation' => __('cancellations.errors.'.$reason)]);
    }
}
