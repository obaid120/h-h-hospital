<?php

namespace App\Http\Controllers;

use App\Http\Requests\CallNextRequest;
use App\Models\Appointment;
use App\Services\DoctorQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DoctorQueueController extends Controller
{
    public function index(Request $request, DoctorQueueService $queue): View
    {
        Gate::authorize('viewQueue', Appointment::class);

        return view('staff.queue.index', ['state' => $queue->snapshot($request->user())]);
    }

    public function refresh(Request $request, DoctorQueueService $queue): JsonResponse
    {
        Gate::authorize('viewQueue', Appointment::class);
        $state = $queue->snapshot($request->user());

        return response()->json(['html' => view('staff.queue.live', compact('state'))->render(), 'date' => $state['date'], 'current_id' => $state['current']?->id]);
    }

    public function next(CallNextRequest $request, DoctorQueueService $queue): RedirectResponse
    {
        try {
            $current = $queue->callNext($request->user(), $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return to_route('staff.doctor.home')->withErrors(['queue' => __('queue.errors.retry')]);
        }

        return to_route('staff.doctor.home')->with('status', $current ? __('queue.called', ['token' => $current->token_number]) : __('queue.empty'));
    }
}
