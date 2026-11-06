<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnlineBookingRequest;
use App\Models\Doctor;
use App\Services\BookingAvailability;
use App\Services\BookingRejected;
use App\Services\BookingSession;
use App\Services\OnlineBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PDOException;

class OnlineBookingController extends Controller
{
    public function key(Request $request): JsonResponse
    {
        $this->ownerSecret($request);

        return response()->json(['request_key' => (string) Str::uuid(), 'csrf_token' => csrf_token()], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function availability(Request $request, Doctor $doctor, BookingAvailability $availability): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return response()->json(['availability' => $availability->forDate($doctor, $data['date'])], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function month(Request $request, Doctor $doctor, BookingAvailability $availability): JsonResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);

        return response()->json(['calendar' => $availability->forMonth($doctor, $data['month'])], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function store(OnlineBookingRequest $request, OnlineBookingService $bookings): JsonResponse
    {
        try {
            $result = $bookings->reserve($request->validated(), $this->ownerSecret($request));
            $appointment = $result->appointment->load('doctorDay.doctor.user');

            return response()->json([
                'booking' => [
                    'reference' => $appointment->public_reference, 'status' => $appointment->status->value,
                    'patient' => ['name' => $appointment->submitted_name, 'phone' => $appointment->submitted_phone, 'age_years' => $appointment->submitted_age],
                    'doctor' => ['id' => $appointment->doctorDay->doctor_id, 'name' => $appointment->doctorDay->doctor->user->name],
                    'date' => $appointment->doctorDay->date->toDateString(),
                    'normal_fee_minor' => $appointment->normal_fee_minor, 'currency' => config('hospital.currency'),
                ],
                'replayed' => $result->replayed, 'message' => __($result->replayed ? 'booking.replayed' : 'booking.reserved'),
                'confirmation_url' => route('booking.confirmation', $appointment->public_reference),
            ], $result->replayed ? 200 : 201, ['Cache-Control' => 'no-store, private']);
        } catch (BookingRejected $exception) {
            return response()->json(['error' => ['code' => $exception->reason, 'message' => $exception->getMessage()], 'availability' => $exception->availability], 409, ['Cache-Control' => 'no-store, private']);
        } catch (PDOException $exception) {
            report($exception);

            return response()->json(['error' => ['code' => 'temporarily_unavailable', 'message' => __('booking.errors.temporarily_unavailable')]], 503, ['Cache-Control' => 'no-store, private']);
        }
    }

    private function ownerSecret(Request $request): string
    {
        return app(BookingSession::class)->secret($request);
    }
}
