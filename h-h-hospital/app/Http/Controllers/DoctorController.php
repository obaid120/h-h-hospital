<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Http\Requests\DoctorDateOverrideRequest;
use App\Http\Requests\DoctorProfileRequest;
use App\Http\Requests\DoctorWeeklyScheduleRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Services\DoctorDayManager;
use App\Services\DoctorProfileManager;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Doctor::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $doctors = Doctor::query()->with(['user', 'weeklySchedules'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('specialization', 'like', '%'.$search.'%')->orWhereHas('user', fn (Builder $users): Builder => $users->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('is_active', $status === 'active'))
            ->orderBy('specialization')->orderBy('id')->paginate(15)->withQueryString();

        return view('staff.doctors.index', compact('doctors', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', Doctor::class);
        $accounts = User::query()->where('role', StaffRole::Doctor)->whereDoesntHave('doctor')->orderBy('name')->pluck('name', 'id');

        return view('staff.doctors.form', ['doctor' => new Doctor(['is_active' => true, 'normal_fee_minor' => 0, 'priority_surcharge_minor' => 0]), 'accounts' => $accounts]);
    }

    public function store(DoctorProfileRequest $request, DoctorProfileManager $manager): RedirectResponse
    {
        $doctor = $manager->save($request->user(), null, $request->validated(), $request->file('photo'));

        return to_route('staff.admin.doctors.edit', $doctor)->with('status', __('doctors.created'));
    }

    public function edit(Doctor $doctor): View
    {
        Gate::authorize('update', $doctor);

        return view('staff.doctors.form', ['doctor' => $doctor->load('user'), 'accounts' => collect()]);
    }

    public function update(DoctorProfileRequest $request, Doctor $doctor, DoctorProfileManager $manager): RedirectResponse
    {
        $manager->save($request->user(), $doctor, $request->validated(), $request->file('photo'));

        return to_route('staff.admin.doctors.edit', $doctor)->with('status', __('doctors.updated'));
    }

    public function show(Request $request, Doctor $doctor, DoctorDayManager $manager): View
    {
        Gate::authorize('view', $doctor);
        $filters = $request->validate(['from' => ['nullable', 'date_format:Y-m-d']]);
        $from = CarbonImmutable::parse($filters['from'] ?? now(config('hospital.timezone'))->toDateString());
        $doctor->load(['user', 'weeklySchedules']);
        $days = $doctor->days()->whereBetween('date', [$from->toDateString(), $from->addDays(30)->toDateString()])->get()->keyBy(fn ($day): string => $day->date->toDateString());
        $doctor->setRelation('days', $days->values());
        $calendar = collect(range(0, 30))->map(function (int $offset) use ($from, $doctor, $days, $manager): array {
            $date = $from->addDays($offset)->toDateString();
            $day = $days->get($date);

            return ['date' => $date, 'day' => $day, 'hours' => $manager->hoursFor($doctor, $date, $day)];
        });

        return view('staff.doctors.show', compact('doctor', 'calendar', 'from'));
    }

    public function weekly(DoctorWeeklyScheduleRequest $request, Doctor $doctor, DoctorDayManager $manager): RedirectResponse
    {
        $manager->weekly($request->user(), $doctor, $request->validated('days'));

        return to_route('staff.doctors.show', $doctor)->with('status', __('doctors.weekly_saved'));
    }

    public function override(DoctorDateOverrideRequest $request, Doctor $doctor, DoctorDayManager $manager): RedirectResponse
    {
        $manager->override($request->user(), $doctor, $request->validated());

        return to_route('staff.doctors.show', ['doctor' => $doctor, 'from' => $request->validated('date')])->with('status', __('doctors.override_saved'));
    }
}
