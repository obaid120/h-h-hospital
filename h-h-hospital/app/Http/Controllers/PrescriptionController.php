<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\PrescriptionRequest;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    public function edit(Appointment $appointment): View|RedirectResponse
    {
        if ($appointment->status === AppointmentStatus::Completed && $appointment->prescription?->finalized_at) {
            Gate::authorize('viewPrescription', $appointment);

            return to_route('staff.prescriptions.show', $appointment->prescription);
        }
        Gate::authorize('writePrescription', $appointment);
        $appointment->load(['doctorDay.doctor.user', 'patient', 'prescription.items']);
        abort_unless($appointment->doctorDay->current_appointment_id === $appointment->id && $appointment->consultation_started_at && $appointment->patient, 403);

        return view('staff.prescriptions.editor', ['appointment' => $appointment, 'prescription' => $appointment->prescription, 'requestKey' => (string) Str::uuid(), 'completionKey' => (string) Str::uuid()]);
    }

    public function store(PrescriptionRequest $request, Appointment $appointment, PrescriptionService $service): RedirectResponse
    {
        try {
            $prescription = $service->save($request->user(), $appointment, $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['prescription' => __('prescription.errors.retry')]);
        }

        return to_route($prescription->finalized_at ? 'staff.prescriptions.show' : 'staff.prescriptions.edit', $prescription->finalized_at ? $prescription : $appointment)->with('status', __('prescription.saved'));
    }

    public function complete(PrescriptionRequest $request, Appointment $appointment, PrescriptionService $service): RedirectResponse
    {
        try {
            $prescription = $service->complete($request->user(), $appointment, $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return back()->withErrors(['prescription' => __('prescription.errors.retry')]);
        }

        return to_route('staff.prescriptions.show', $prescription)->with('status', __('prescription.completed'));
    }

    public function show(Request $request, Prescription $prescription): View
    {
        Gate::authorize('viewPrescription', $prescription->appointment);
        $prescription->load('items');

        return view('staff.prescriptions.show', ['prescription' => $prescription, 'printView' => $request->routeIs('staff.prescriptions.print')]);
    }
}
