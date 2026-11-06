<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Http\Requests\ReceptionPaymentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\BookingAvailability;
use App\Services\BookingRejected;
use App\Services\CancellationService;
use App\Services\PatientPhone;
use App\Services\ReceptionPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReceptionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Appointment::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'date' => ['nullable', 'date_format:Y-m-d'],
            'doctor_id' => ['nullable', 'integer', Rule::exists(Doctor::class, 'id')],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)], 'older_pending' => ['nullable', 'boolean'],
        ]);
        $filters['status'] ??= 'pending';
        $filters['date'] ??= now(config('hospital.timezone'))->toDateString();
        $search = trim($filters['q'] ?? '');
        $query = Appointment::query()->with(['doctorDay.doctor.user', 'patient', 'payment']);
        if ($search !== '') {
            $phone = PatientPhone::normalize($search);
            $query->where(function (Builder $query) use ($search, $phone): void {
                $query->where('public_reference', Str::upper($search));
                if ($phone) {
                    $query->orWhere('submitted_phone', $phone)->orWhereHas('patient', fn (Builder $patients): Builder => $patients->where('phone', $phone));
                }
            });
        }
        if (! empty($filters['doctor_id'])) {
            $query->whereHas('doctorDay', fn (Builder $days): Builder => $days->where('doctor_id', $filters['doctor_id']));
        }
        if (! empty($filters['older_pending'])) {
            $query->where('status', AppointmentStatus::Pending)->whereHas('doctorDay', fn (Builder $days): Builder => $days->where('date', '<', now(config('hospital.timezone'))->toDateString()));
        } else {
            $query->where('status', $filters['status']);
            if ($search === '') {
                $query->whereHas('doctorDay', fn (Builder $days): Builder => $days->where('date', $filters['date']));
            }
        }
        $appointments = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $doctors = Doctor::query()->with('user')->orderBy('id')->get();

        return view('staff.reception.index', compact('appointments', 'filters', 'doctors'));
    }

    public function show(Request $request, Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);
        $appointment->load(['doctorDay.doctor.user', 'patient', 'payment', 'cancellation']);
        $refundedMinor = $appointment->payment ? (int) $appointment->payment->refunds()->sum('amount_minor') : 0;
        $canCancel = CancellationService::eligible($appointment, $appointment->doctorDay) && ! $appointment->cancellation;
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $lookup = trim($filters['q'] ?? $appointment->submitted_phone);
        $phone = PatientPhone::normalize($lookup);
        $matches = Patient::query()->where(function (Builder $query) use ($lookup, $phone): void {
            $query->where('mr_number', Str::upper($lookup));
            if ($phone) {
                $query->orWhere('phone', $phone);
            }
        })->orderBy('name')->get();
        $today = now(config('hospital.timezone'))->toDateString();
        $canArrive = $appointment->source === AppointmentSource::Online && $appointment->status === AppointmentStatus::Pending && $appointment->doctorDay->date->toDateString() === $today;

        return view('staff.reception.show', ['appointment' => $appointment, 'lookup' => $lookup, 'matches' => $matches, 'canArrive' => $canArrive, 'requestKey' => (string) Str::uuid(), 'canCancel' => $canCancel, 'cancellationKey' => (string) Str::uuid(), 'refundedMinor' => $refundedMinor]);
    }

    public function create(Request $request, BookingAvailability $availability): View
    {
        Gate::authorize('create', Appointment::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'doctor_id' => ['nullable', 'integer', Rule::exists(Doctor::class, 'id')], 'patient_id' => ['nullable', 'integer', Rule::exists(Patient::class, 'id')]]);
        $search = trim($filters['q'] ?? '');
        $phone = PatientPhone::normalize($search);
        $patients = Patient::query()->where(function (Builder $query) use ($search, $phone, $filters): void {
            $query->where('mr_number', Str::upper($search));
            if ($phone) {
                $query->orWhere('phone', $phone);
            }
            if (! empty($filters['patient_id'])) {
                $query->orWhere('id', $filters['patient_id']);
            }
        })->orderBy('name')->get();
        $visits = Appointment::query()->with(['doctorDay.doctor.user', 'patient'])
            ->where(function (Builder $query) use ($phone, $patients, $search): void {
                $query->where('public_reference', Str::upper($search))->orWhereIn('patient_id', $patients->modelKeys());
                if ($phone) {
                    $query->orWhere('submitted_phone', $phone);
                }
            })->orderByDesc('id')->paginate(10)->withQueryString();
        $today = now(config('hospital.timezone'))->toDateString();
        $doctors = Doctor::query()->publiclyVisible()->with(['user', 'weeklySchedules', 'days' => fn ($query) => $query->where('date', $today)])->orderBy('id')->get();
        $states = $doctors->mapWithKeys(fn (Doctor $doctor): array => [$doctor->id => $availability->forDate($doctor, $today)]);

        return view('staff.reception.walk-in', ['filters' => $filters, 'phone' => $phone, 'matches' => $patients, 'visits' => $visits, 'doctors' => $doctors, 'states' => $states, 'today' => $today, 'requestKey' => (string) Str::uuid()]);
    }

    public function confirm(ReceptionPaymentRequest $request, Appointment $appointment, ReceptionPaymentService $service): JsonResponse|RedirectResponse
    {
        Gate::authorize('confirm', $appointment);
        try {
            $payment = $service->confirmOnline($request->user(), $appointment, $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return StaffOperationResponse::retry($request, 'payment', __('reception.errors.retry'));
        }

        return StaffOperationResponse::success($request, route('staff.receipts.show', $payment), __('reception.paid'));
    }

    public function store(ReceptionPaymentRequest $request, ReceptionPaymentService $service): JsonResponse|RedirectResponse
    {
        try {
            $payment = $service->walkIn($request->user(), $request->validated());
        } catch (BookingRejected $exception) {
            throw ValidationException::withMessages(['payment' => $exception->getMessage()]);
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return StaffOperationResponse::retry($request, 'payment', __('reception.errors.retry'));
        }

        return StaffOperationResponse::success($request, route('staff.receipts.show', $payment), __('reception.paid'));
    }
}
