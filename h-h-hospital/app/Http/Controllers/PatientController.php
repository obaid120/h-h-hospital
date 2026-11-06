<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Http\Requests\PatientRegistrationRequest;
use App\Models\Patient;
use App\Services\PatientPhone;
use App\Services\PatientRegistrar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Patient::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'search_by' => ['nullable', Rule::in(['phone', 'mr', 'name'])]]);
        $filters['search_by'] ??= 'phone';
        $query = Patient::query()->visibleTo($request->user());
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            if ($filters['search_by'] === 'phone') {
                $phone = PatientPhone::normalize($search);
                if (! $phone) {
                    throw ValidationException::withMessages(['q' => __('patients.phone_error')]);
                }
                $query->where('phone', $phone);
            } elseif ($filters['search_by'] === 'mr') {
                $query->where('mr_number', Str::upper($search));
            } else {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%']);
            }
        }
        $patients = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('staff.patients.index', compact('patients', 'filters'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Patient::class);
        $filters = $request->validate(['phone' => ['nullable', 'string', 'max:40']]);
        $phone = isset($filters['phone']) ? PatientPhone::normalize($filters['phone']) : null;
        if (! empty($filters['phone']) && ! $phone) {
            throw ValidationException::withMessages(['phone' => __('patients.phone_error')]);
        }
        $matches = Patient::query()->where('phone', $phone ?? '')->orderBy('name')->paginate(10)->withQueryString();

        return view('staff.patients.form', ['phone' => $phone, 'matches' => $matches, 'registrationKey' => (string) Str::uuid()]);
    }

    public function store(PatientRegistrationRequest $request, PatientRegistrar $registrar): RedirectResponse
    {
        $patient = $registrar->register($request->user(), $request->validated());

        return to_route('staff.patients.show', $patient)->with('status', __('patients.registered', ['mr' => $patient->mr_number]));
    }

    public function show(Request $request, Patient $patient): View
    {
        Gate::authorize('view', $patient);
        $visits = $patient->appointments()->visibleTo($request->user())->with('doctorDay.doctor.user')->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();
        if ($request->user()->role !== StaffRole::Receptionist) {
            $visits->getCollection()->load('prescription:id,appointment_id,finalized_at');
        }

        return view('staff.patients.show', compact('patient', 'visits'));
    }
}
