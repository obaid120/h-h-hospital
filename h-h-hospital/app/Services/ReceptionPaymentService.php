<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReceptionPaymentService
{
    public function confirmOnline(User $actor, Appointment $appointment, array $input): Payment
    {
        $data = $this->validated($input);
        $fingerprint = ReceptionInput::fingerprint($data, 'online', $appointment->id);
        $anchor = Appointment::query()->with('doctorDay')->findOrFail($appointment->id);

        return $this->retry(function () use ($actor, $anchor, $data, $fingerprint): Payment {
            $cashier = $this->cashier($actor);
            $doctor = Doctor::query()->lockForUpdate()->findOrFail($anchor->doctorDay->doctor_id);
            $day = DoctorDay::query()->lockForUpdate()->findOrFail($anchor->doctor_day_id);
            $visit = Appointment::query()->lockForUpdate()->findOrFail($anchor->id);
            if ($visit->doctor_day_id !== $day->id || $visit->source !== AppointmentSource::Online) {
                $this->reject('invalid_visit');
            }
            $sameKey = Payment::query()->where('request_key', $data['request_key'])->first();
            if ($sameKey) {
                return $this->sameRequest($sameKey, $fingerprint);
            }
            $paid = Payment::query()->where('appointment_id', $visit->id)->first();
            if ($paid) {
                if ($this->matchesPatient($paid, $visit, $data)) {
                    return $paid;
                }
                $this->reject('already_paid');
            }
            if ($visit->status !== AppointmentStatus::Pending || $visit->token_number !== null) {
                $this->reject('invalid_visit');
            }
            if ($day->date->toDateString() !== now(config('hospital.timezone'))->toDateString()) {
                $this->reject('arrival_date');
            }
            $patient = $this->patient($cashier, $data);
            $visit->patient_id = $patient->id;
            $visit->verified_age = (int) $data['age_years'];
            $visit->is_priority = false;
            $visit->priority_surcharge_minor = 0;

            return $this->collect($cashier, $doctor, $day, $visit, $patient, $data, $fingerprint);
        });
    }

    public function walkIn(User $actor, array $input): Payment
    {
        $data = $this->validated($input, walkIn: true);
        $fingerprint = ReceptionInput::fingerprint($data, 'walk_in', (int) $data['doctor_id']);

        return $this->retry(function () use ($actor, $data, $fingerprint): Payment {
            $cashier = $this->cashier($actor);
            Doctor::query()->lockForUpdate()->findOrFail($data['doctor_id']);
            $existing = Payment::query()->where('request_key', $data['request_key'])->first();
            if ($existing) {
                return $this->sameRequest($existing, $fingerprint);
            }
            if ($data['date'] !== now(config('hospital.timezone'))->toDateString()) {
                $this->reject('arrival_date');
            }
            $payment = null;
            app(SeatReservationService::class)->reserve((int) $data['doctor_id'], $data['date'], function (Doctor $doctor, DoctorDay $day) use ($cashier, $data, $fingerprint, &$payment): Appointment {
                $patient = $this->patient($cashier, $data);
                $visit = new Appointment([
                    'public_reference' => app(BookingReference::class)->generate(), 'request_key' => $data['request_key'],
                    'doctor_day_id' => $day->id, 'patient_id' => $patient->id,
                    'submitted_name' => $patient->name, 'submitted_phone' => $patient->phone,
                    'submitted_age' => (int) $data['age_years'], 'verified_age' => (int) $data['age_years'],
                    'source' => AppointmentSource::WalkIn, 'status' => AppointmentStatus::Pending,
                    'is_priority' => (bool) ($data['is_priority'] ?? false), 'normal_fee_minor' => $doctor->normal_fee_minor,
                    'priority_surcharge_minor' => ! empty($data['is_priority']) ? $doctor->priority_surcharge_minor : 0,
                ]);
                $hours = app(DoctorDayManager::class)->hoursFor($doctor, $data['date'], $day);
                $visit->opd_opens_at = $hours['opens_at'];
                $visit->opd_closes_at = $hours['closes_at'];
                $visit->save();
                $payment = $this->collect($cashier, $doctor, $day, $visit, $patient, $data, $fingerprint);

                return $visit;
            });

            return $payment;
        });
    }

    private function validated(array $input, bool $walkIn = false): array
    {
        return Validator::make(ReceptionInput::normalize($input), ReceptionInput::rules($walkIn), ['phone.regex' => __('patients.phone_error')])->validate();
    }

    private function cashier(User $actor): User
    {
        $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
        abort_unless($fresh->is_active && $fresh->role === StaffRole::Receptionist && $fresh->auth_version === $actor->auth_version, 403);

        return $fresh;
    }

    private function patient(User $cashier, array $data): Patient
    {
        if ($data['patient_mode'] === 'existing') {
            return Patient::query()->lockForUpdate()->findOrFail($data['patient_id']);
        }

        return app(PatientRegistrar::class)->register($cashier, [
            'name' => $data['name'], 'phone' => $data['phone'], 'age_years' => $data['age_years'],
            'registration_key' => $data['request_key'], 'identity_confirmed' => true,
        ]);
    }

    private function collect(User $cashier, Doctor $doctor, DoctorDay $day, Appointment $visit, Patient $patient, array $data, string $fingerprint): Payment
    {
        $visit->token_number = app(TokenAllocator::class)->next($day);
        $visit->status = AppointmentStatus::Confirmed;
        $visit->confirmed_at = now();
        $visit->receptionist_id = $cashier->id;
        $visit->save();

        $hospital = app(HospitalSettings::class)->current();
        $payment = Payment::create([
            'appointment_id' => $visit->id, 'receipt_number' => app(ReceiptReference::class)->generate(),
            'request_key' => $data['request_key'], 'request_payload_hash' => $fingerprint,
            'base_fee_minor' => $visit->normal_fee_minor, 'priority_charge_minor' => $visit->priority_surcharge_minor,
            'total_minor' => $visit->normal_fee_minor + $visit->priority_surcharge_minor, 'is_priority' => $visit->is_priority,
            'currency' => config('hospital.currency'), 'method' => 'cash',
            'cashier_id' => $cashier->id, 'cashier_name' => $cashier->name, 'paid_at' => $visit->confirmed_at,
            'timezone' => config('hospital.timezone'), 'patient_name' => $patient->name, 'patient_mr' => $patient->mr_number,
            'patient_phone' => $patient->phone, 'patient_age' => (int) $data['age_years'],
            'doctor_name' => $doctor->user->name, 'opd_date' => $day->date->toDateString(), 'token_number' => $visit->token_number,
            'hospital_name' => $hospital['name'], 'hospital_address' => $hospital['address'], 'hospital_phone' => $hospital['phone'], 'hospital_footer' => $hospital['print_footer'],
        ]);
        $audit = app(ActivityRecorder::class);
        $audit->record($cashier, 'appointment.confirmed', 'appointment', $visit->id, ['status' => 'pending'], ['status' => 'confirmed', 'source' => $visit->source->value, 'token_number' => $visit->token_number], $doctor->id, 'confirmation:'.$visit->id, $payment->currency);
        $audit->record($cashier, 'payment.collected', 'payment', $payment->id, [], ['receipt_number' => $payment->receipt_number, 'total_minor' => $payment->total_minor, 'token_number' => $visit->token_number], $doctor->id, 'payment:'.$payment->id, $payment->currency);
        if ($visit->is_priority) {
            $audit->record($cashier, 'appointment.priority', 'appointment', $visit->id, [], ['is_priority' => true, 'priority_surcharge_minor' => $payment->priority_charge_minor], $doctor->id, 'priority:'.$visit->id, $payment->currency);
        }

        return $payment;
    }

    private function matchesPatient(Payment $paid, Appointment $visit, array $data): bool
    {
        if ($paid->patient_age !== (int) $data['age_years']) {
            return false;
        }

        return $data['patient_mode'] === 'existing'
            ? $visit->patient_id === (int) $data['patient_id']
            : $paid->patient_name === $data['name'] && $paid->patient_phone === $data['phone'];
    }

    private function sameRequest(Payment $payment, string $fingerprint): Payment
    {
        if (! hash_equals($payment->request_payload_hash, $fingerprint)) {
            $this->reject('request_conflict');
        }

        return $payment;
    }

    /** @param \Closure(): Payment $operation */
    private function retry(\Closure $operation): Payment
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction($operation, attempts: 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Payment retry attempts exhausted.');
    }

    private function reject(string $reason): never
    {
        throw ValidationException::withMessages(['payment' => __('reception.errors.'.$reason)]);
    }
}
