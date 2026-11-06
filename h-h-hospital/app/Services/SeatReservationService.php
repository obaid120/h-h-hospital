<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

class SeatReservationService
{
    /** @param Closure(Doctor, DoctorDay): Appointment $createAppointment */
    public function reserve(int $doctorId, string $date, Closure $createAppointment): Appointment
    {
        return DB::transaction(function () use ($doctorId, $date, $createAppointment): Appointment {
            $doctor = Doctor::query()->lockForUpdate()->findOrFail($doctorId);
            $doctor->setRelation('user', User::query()->lockForUpdate()->findOrFail($doctor->user_id));
            $day = app(DoctorDayManager::class)->forDate($doctor, $date);
            $day = DoctorDay::query()->lockForUpdate()->findOrFail($day->id);
            $availability = app(BookingAvailability::class)->forDate($doctor, $date, $day);
            if ($availability['state'] !== 'available') {
                throw new BookingRejected($availability['state'], $availability);
            }

            $appointment = $createAppointment($doctor, $day);
            if (! $appointment->wasRecentlyCreated || $appointment->doctor_day_id !== $day->id) {
                throw new LogicException('Seat reservation must save an appointment on the locked doctor day.');
            }
            $updated = DoctorDay::query()->whereKey($day->id)->where('used_capacity', '<', DoctorDay::CAPACITY)->increment('used_capacity');
            if ($updated !== 1) {
                throw new BookingRejected('full');
            }

            return $appointment;
        }, attempts: 3);
    }
}
