<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\Doctor;
use App\Models\DoctorDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class BookingAvailability
{
    /** @return array{doctor_id: int, date: string, state: string, capacity: int, used_capacity: int, remaining_seats: int, opens_at: ?string, closes_at: ?string, timezone: string} */
    public function forDate(Doctor $doctor, string $date, ?DoctorDay $day = null): array
    {
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();
        $doctor->loadMissing(['user', 'weeklySchedules']);
        $day ??= $doctor->relationLoaded('days')
            ? $doctor->days->first(fn (DoctorDay $candidate): bool => $candidate->date->toDateString() === $date)
            : $doctor->days()->where('date', $date)->first();
        $hours = app(DoctorDayManager::class)->hoursFor($doctor, $date, $day);
        $now = CarbonImmutable::now(config('hospital.timezone'));
        $today = $now->startOfDay();
        $selected = CarbonImmutable::parse($date, config('hospital.timezone'))->startOfDay();
        $remaining = max(0, DoctorDay::CAPACITY - ($day?->used_capacity ?? 0));

        $state = match (true) {
            ! $doctor->is_active || ! $doctor->user->is_active || $doctor->user->role !== StaffRole::Doctor => 'inactive',
            $selected->lessThan($today) => 'past',
            $selected->greaterThan($today->addDays(max(0, (int) config('hospital.booking.horizon_days')))) => 'outside_horizon',
            $hours === null => 'closed',
            $now->greaterThanOrEqualTo(CarbonImmutable::parse($date.' '.$hours['closes_at'], config('hospital.timezone'))->subMinutes(max(0, (int) config('hospital.booking.cutoff_minutes_before_closing')))) => 'cutoff',
            $remaining === 0 => 'full',
            default => 'available',
        };

        return [
            'doctor_id' => $doctor->id, 'date' => $date, 'state' => $state,
            'capacity' => DoctorDay::CAPACITY, 'used_capacity' => $day?->used_capacity ?? 0,
            'remaining_seats' => $state === 'available' ? $remaining : 0,
            'opens_at' => $hours['opens_at'] ?? null, 'closes_at' => $hours['closes_at'] ?? null,
            'timezone' => config('hospital.timezone'),
        ];
    }

    /** @return array<string, mixed> */
    public function forMonth(Doctor $doctor, string $month): array
    {
        Validator::make(['month' => $month], ['month' => ['required', 'date_format:Y-m']])->validate();
        $today = CarbonImmutable::now(config('hospital.timezone'))->startOfDay();
        $first = $today->startOfMonth();
        $last = $today->addDays(max(0, (int) config('hospital.booking.horizon_days')))->startOfMonth();
        $start = CarbonImmutable::createFromFormat('!Y-m', $month, config('hospital.timezone'));
        Validator::make(['month' => $start->toDateString()], ['month' => ['after_or_equal:'.$first->toDateString(), 'before_or_equal:'.$last->toDateString()]])->validate();
        $doctor = clone $doctor;
        $doctor->loadMissing(['user', 'weeklySchedules']);
        $doctor->setRelation('days', $doctor->days()->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()])->get());
        $days = [];
        for ($offset = 0; $offset < $start->daysInMonth; $offset++) {
            $days[] = $this->forDate($doctor, $start->addDays($offset)->toDateString());
        }

        return ['month' => $month, 'today' => $today->toDateString(), 'last_date' => $today->addDays(max(0, (int) config('hospital.booking.horizon_days')))->toDateString(), 'first_month' => $first->format('Y-m'), 'last_month' => $last->format('Y-m'), 'timezone' => config('hospital.timezone'), 'days' => $days];
    }
}
