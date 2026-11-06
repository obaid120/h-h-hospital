<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LogicException;

class OnlineBookingService
{
    /** @param array<string, mixed> $input */
    public function reserve(array $input, string $ownerSecret): BookingResult
    {
        if (strlen($ownerSecret) < 32) {
            throw new InvalidArgumentException('A private booking-session secret is required.');
        }
        $valid = Validator::make(BookingInput::normalize($input), BookingInput::rules(), ['phone.regex' => __('patients.phone_error')])->validate();
        $data = ['doctor_id' => (int) $valid['doctor_id'], 'date' => $valid['date'], 'name' => $valid['name'], 'phone' => $valid['phone'], 'age_years' => (int) $valid['age_years']];
        $ownerHash = hash('sha256', $ownerSecret);
        $payloadHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $key = $valid['request_key'];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $existing = Appointment::query()->where('request_key', $key)->first();
            if ($existing) {
                return $this->replay($existing, $ownerHash, $payloadHash);
            }
            try {
                return DB::transaction(function () use ($data, $key, $ownerHash, $payloadHash): BookingResult {
                    Doctor::query()->lockForUpdate()->findOrFail($data['doctor_id']);
                    $existing = Appointment::query()->where('request_key', $key)->first();
                    if ($existing) {
                        return $this->replay($existing, $ownerHash, $payloadHash);
                    }
                    $appointment = app(SeatReservationService::class)->reserve($data['doctor_id'], $data['date'], function (Doctor $doctor, DoctorDay $day) use ($data, $key, $ownerHash, $payloadHash): Appointment {
                        $appointment = new Appointment([
                            'public_reference' => app(BookingReference::class)->generate(), 'request_key' => $key,
                            'doctor_day_id' => $day->id, 'submitted_name' => $data['name'], 'submitted_phone' => $data['phone'],
                            'submitted_age' => $data['age_years'], 'source' => AppointmentSource::Online,
                            'status' => AppointmentStatus::Pending, 'is_priority' => false,
                            'normal_fee_minor' => $doctor->normal_fee_minor, 'priority_surcharge_minor' => 0,
                        ]);
                        $appointment->booking_owner_hash = $ownerHash;
                        $appointment->request_payload_hash = $payloadHash;
                        $hours = app(DoctorDayManager::class)->hoursFor($doctor, $data['date'], $day);
                        $appointment->opd_opens_at = $hours['opens_at'];
                        $appointment->opd_closes_at = $hours['closes_at'];
                        $appointment->save();

                        return $appointment;
                    });

                    return new BookingResult($appointment);
                }, attempts: 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = Appointment::query()->where('request_key', $key)->first();
                if ($existing) {
                    return $this->replay($existing, $ownerHash, $payloadHash);
                }
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new LogicException('Booking attempts exhausted.');
    }

    private function replay(Appointment $appointment, string $ownerHash, string $payloadHash): BookingResult
    {
        if (! hash_equals($appointment->booking_owner_hash ?? '', $ownerHash) || ! hash_equals($appointment->request_payload_hash ?? '', $payloadHash)) {
            throw new BookingRejected('request_conflict');
        }

        return new BookingResult($appointment, replayed: true);
    }
}
