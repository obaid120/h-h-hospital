<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancellationRequest;
use App\Models\Appointment;
use App\Services\CancellationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CancellationController extends Controller
{
    public function store(CancellationRequest $request, Appointment $appointment, CancellationService $service): JsonResponse|RedirectResponse
    {
        try {
            $cancellation = $service->cancel($request->user(), $appointment, $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return StaffOperationResponse::retry($request, 'cancellation', __('cancellations.errors.retry'));
        }

        return StaffOperationResponse::success($request, route('staff.appointments.show', $appointment), $cancellation->wasRecentlyCreated ? __('cancellations.cancelled') : __('cancellations.already_cancelled'));
    }
}
