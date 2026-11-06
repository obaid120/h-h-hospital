<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    public function save(User $actor, Appointment $appointment, array $input): Prescription
    {
        $data = Validator::make(PrescriptionInput::normalize($input), PrescriptionInput::rules())->validate();
        $hash = PrescriptionInput::fingerprint($appointment->id, $data);
        $anchor = Appointment::query()->with('doctorDay')->findOrFail($appointment->id);

        try {
            return DB::transaction(function () use ($actor, $anchor, $data, $hash): Prescription {
                [$doctor, $day, $visit] = $this->context($actor, $anchor);
                $prescription = Prescription::query()->where('appointment_id', $visit->id)->lockForUpdate()->first();
                $keyOwner = Prescription::query()->where('last_save_key', $data['request_key'])->first();
                if ($keyOwner && ($keyOwner->appointment_id !== $visit->id || ! hash_equals($keyOwner->last_save_hash, $hash))) {
                    $this->reject('key_conflict');
                }
                if ($keyOwner) {
                    return $keyOwner->load('items');
                }
                $this->eligible($day, $visit);
                if ($prescription?->finalized_at || ($prescription?->version ?? 0) !== (int) $data['version']) {
                    $this->reject('conflict');
                }
                $hospital = app(HospitalSettings::class)->current();
                $prescription ??= new Prescription([
                    'appointment_id' => $visit->id, 'doctor_id' => $doctor->id, 'authored_by' => $actor->id,
                    'patient_name' => $visit->patient->name, 'patient_mr' => $visit->patient->mr_number, 'patient_age' => $visit->verified_age,
                    'doctor_name' => $doctor->user->name, 'doctor_specialization' => $doctor->specialization,
                    'hospital_name' => $hospital['name'], 'hospital_address' => $hospital['address'],
                    'hospital_phone' => $hospital['phone'], 'hospital_footer' => $hospital['print_footer'], 'timezone' => config('hospital.timezone'),
                    'opd_date' => $day->date->toDateString(), 'token_number' => $visit->token_number, 'version' => 0,
                ]);
                $prescription->fill(['notes' => $data['notes'] ?? null, 'advice' => $data['advice'] ?? null, 'draft_saved_at' => now(), 'last_save_key' => $data['request_key'], 'last_save_hash' => $hash, 'version' => $prescription->version + 1]);
                $prescription->save();
                $prescription->items()->delete();
                foreach ($data['items'] as $position => $row) {
                    $prescription->items()->create($row + ['position' => $position]);
                }

                return $prescription->load('items');
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            $this->reject('conflict');
        }
    }

    public function complete(User $actor, Appointment $appointment, array $input): Prescription
    {
        $data = Validator::make(PrescriptionInput::normalize($input, completion: true), PrescriptionInput::rules(completion: true))->validate();
        $anchor = Appointment::query()->with('doctorDay')->findOrFail($appointment->id);

        try {
            return DB::transaction(function () use ($actor, $anchor, $data): Prescription {
                [, $day, $visit] = $this->context($actor, $anchor);
                $prescription = Prescription::query()->where('appointment_id', $visit->id)->lockForUpdate()->first();
                $keyOwner = Prescription::query()->where('completion_key', $data['completion_key'])->first();
                if ($keyOwner && $keyOwner->appointment_id !== $visit->id) {
                    $this->reject('key_conflict');
                }
                if ($visit->status === AppointmentStatus::Completed && $prescription?->finalized_at) {
                    return $prescription->load('items');
                }
                $this->eligible($day, $visit);
                if (! $prescription || $prescription->finalized_at || $prescription->version !== (int) $data['version']) {
                    $this->reject('save_first');
                }
                $prescription->load('items');
                if (blank($prescription->notes) && blank($prescription->advice) && $prescription->items->isEmpty()) {
                    $this->reject('empty');
                }
                foreach ($prescription->items as $item) {
                    if (blank($item->dose) || blank($item->frequency) || blank($item->duration)) {
                        $this->reject('incomplete_lines');
                    }
                }
                $prescription->finalized_at = now();
                $prescription->finalized_by = $actor->id;
                $prescription->completion_key = $data['completion_key'];
                $prescription->version++;
                $prescription->save();
                $visit->status = AppointmentStatus::Completed;
                $visit->completed_at = $prescription->finalized_at;
                $visit->save();
                $day->current_appointment_id = null;
                $day->queue_version++;
                $day->save();
                app(ActivityRecorder::class)->record(User::findOrFail($actor->id), 'appointment.completed', 'appointment', $visit->id, ['status' => 'confirmed'], ['status' => 'completed', 'prescription_id' => $prescription->id], $day->doctor_id, 'completion:'.$prescription->id);

                return $prescription;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            $this->reject('conflict');
        }
    }

    /** @return array{Doctor, DoctorDay, Appointment} */
    private function context(User $actor, Appointment $appointment): array
    {
        $doctor = Doctor::query()->lockForUpdate()->findOrFail($appointment->doctorDay->doctor_id);
        $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
        abort_unless($fresh->is_active && $fresh->role === StaffRole::Doctor && $fresh->auth_version === $actor->auth_version && $doctor->user_id === $fresh->id, 403);
        $doctor->setRelation('user', $fresh);
        $day = DoctorDay::query()->lockForUpdate()->findOrFail($appointment->doctor_day_id);
        $visit = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
        abort_unless($visit->doctor_day_id === $day->id, 403);

        return [$doctor, $day, $visit];
    }

    private function eligible(DoctorDay $day, Appointment $visit): void
    {
        if ($visit->status !== AppointmentStatus::Confirmed || ! $visit->consultation_started_at || $day->current_appointment_id !== $visit->id || ! $visit->patient_id || $visit->verified_age === null || $visit->token_number === null || ! $visit->payment()->exists()) {
            $this->reject('not_current');
        }
    }

    private function reject(string $reason): never
    {
        throw ValidationException::withMessages(['prescription' => __('prescription.errors.'.$reason)]);
    }
}
