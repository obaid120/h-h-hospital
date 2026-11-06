<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\DoctorWeeklySchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DoctorDayManager
{
    public function forDate(Doctor $doctor, string $date): DoctorDay
    {
        $this->validateDate($date);

        return DB::transaction(function () use ($doctor, $date): DoctorDay {
            $locked = Doctor::query()->lockForUpdate()->findOrFail($doctor->id);

            return $locked->days()->firstOrCreate(['date' => $date]);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function override(User $actor, Doctor $doctor, array $data): DoctorDay
    {
        return DB::transaction(function () use ($actor, $doctor, $data): DoctorDay {
            $this->authorizeChange($actor, $doctor);
            $day = $this->forDate($doctor, $data['date']);
            $before = $day->only(['schedule_override', 'opens_at', 'closes_at', 'reason']);
            $day->schedule_override = $data['schedule_override'] === 'weekly' ? null : $data['schedule_override'];
            $day->opens_at = $day->schedule_override === 'open' ? $data['opens_at'] : null;
            $day->closes_at = $day->schedule_override === 'open' ? $data['closes_at'] : null;
            $day->reason = $data['reason'];
            $day->updated_by = $actor->id;
            $day->save();
            $after = $day->only(['schedule_override', 'opens_at', 'closes_at', 'reason']);
            if ($before !== $after) {
                app(ActivityRecorder::class)->record($actor->fresh(), 'doctor.date_changed', 'doctor', $doctor->id, $before, ['date' => $day->date->toDateString()] + $after, $doctor->id);
            }

            return $day;
        }, attempts: 3);
    }

    /** @param array<int, array<string, mixed>> $days */
    public function weekly(User $actor, Doctor $doctor, array $days): void
    {
        DB::transaction(function () use ($actor, $doctor, $days): void {
            $this->authorizeChange($actor, $doctor);
            $locked = Doctor::query()->lockForUpdate()->findOrFail($doctor->id);
            $before = $this->schedule($locked);

            foreach ($days as $weekday => $hours) {
                if ($hours['enabled']) {
                    $locked->weeklySchedules()->updateOrCreate(['weekday' => $weekday], ['opens_at' => $hours['opens_at'], 'closes_at' => $hours['closes_at']]);
                } else {
                    $locked->weeklySchedules()->where('weekday', $weekday)->delete();
                }
            }
            $after = $this->schedule($locked);
            if ($before !== $after) {
                app(ActivityRecorder::class)->record($actor->fresh(), 'doctor.weekly_changed', 'doctor', $doctor->id, ['schedule' => $before], ['schedule' => $after], $doctor->id);
            }
        }, attempts: 3);
    }

    /** @return array{opens_at: string, closes_at: string}|null */
    public function hoursFor(Doctor $doctor, string $date, ?DoctorDay $day = null): ?array
    {
        $this->validateDate($date);
        $day ??= $doctor->relationLoaded('days')
            ? $doctor->days->first(fn (DoctorDay $candidate): bool => $candidate->date->toDateString() === $date)
            : $doctor->days()->where('date', $date)->first();
        if ($day?->schedule_override === 'closed') {
            return null;
        }
        if ($day?->schedule_override === 'open') {
            return ['opens_at' => substr($day->opens_at, 0, 5), 'closes_at' => substr($day->closes_at, 0, 5)];
        }
        $schedule = $doctor->weeklySchedules->firstWhere('weekday', CarbonImmutable::parse($date)->isoWeekday());

        return $schedule ? ['opens_at' => substr($schedule->opens_at, 0, 5), 'closes_at' => substr($schedule->closes_at, 0, 5)] : null;
    }

    private function authorizeChange(User $actor, Doctor $doctor): void
    {
        $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
        abort_unless($fresh->auth_version === $actor->auth_version, 403);
        Gate::forUser($fresh)->authorize('update', $doctor);
    }

    private function schedule(Doctor $doctor): array
    {
        return $doctor->weeklySchedules()->orderBy('weekday')->get()->mapWithKeys(fn (DoctorWeeklySchedule $hours): array => [$hours->weekday => ['opens_at' => substr($hours->opens_at, 0, 5), 'closes_at' => substr($hours->closes_at, 0, 5)]])->all();
    }

    private function validateDate(string $date): void
    {
        if (! preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date) || ! CarbonImmutable::canBeCreatedFromFormat($date, 'Y-m-d') || CarbonImmutable::parse($date)->toDateString() !== $date) {
            throw new \InvalidArgumentException('A valid hospital-local date in Y-m-d format is required.');
        }
    }
}
