<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicBookingFormRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\BookingAvailability;
use App\Services\BookingRejected;
use App\Services\BookingSession;
use App\Services\OnlineBookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PDOException;

class PublicBookingController extends Controller
{
    public function create(Request $request, Doctor $doctor, BookingAvailability $availability, BookingSession $session): Response
    {
        abort_unless(Doctor::query()->publiclyVisible()->whereKey($doctor->id)->exists(), 404);
        $session->secret($request);
        $doctor->load(['user', 'weeklySchedules']);
        $today = CarbonImmutable::now(config('hospital.timezone'));
        $month = $today->format('Y-m');
        $oldDate = $request->old('date');
        if (is_string($oldDate) && Validator::make(['date' => $oldDate], ['date' => ['date_format:Y-m-d']])->passes()) {
            $lastMonth = $today->addDays(max(0, (int) config('hospital.booking.horizon_days')))->format('Y-m');
            $oldMonth = substr($oldDate, 0, 7);
            if ($oldMonth >= $month && $oldMonth <= $lastMonth) {
                $month = $oldMonth;
            }
        }
        $calendar = $availability->forMonth($doctor, $month);
        $requestKey = (string) Str::uuid();
        $view = $request->header('X-Booking-Fragment') === '1' ? 'booking.form' : 'booking.create';

        return response()->view($view, compact('doctor', 'calendar', 'requestKey'), headers: ['Cache-Control' => 'no-store, private']);
    }

    public function store(PublicBookingFormRequest $request, Doctor $doctor, OnlineBookingService $bookings, BookingSession $session): RedirectResponse
    {
        try {
            $result = $bookings->reserve($request->validated(), $session->secret($request));

            return to_route('booking.confirmation', $result->appointment->public_reference)->withHeaders(['Cache-Control' => 'no-store, private']);
        } catch (BookingRejected $exception) {
            return to_route('booking.create', $doctor)->withErrors([$exception->reason === 'request_conflict' ? 'booking' : 'date' => $exception->getMessage()])->withInput($request->safe()->only(['doctor_id', 'date', 'name', 'phone', 'age_years', 'request_key']))->withHeaders(['Cache-Control' => 'no-store, private']);
        } catch (PDOException $exception) {
            report($exception);

            return to_route('booking.create', $doctor)->withErrors(['booking' => __('booking.errors.temporarily_unavailable')])->withInput($request->safe()->only(['doctor_id', 'date', 'name', 'phone', 'age_years', 'request_key']))->with('booking_uncertain', true)->withHeaders(['Cache-Control' => 'no-store, private']);
        }
    }

    public function confirmation(Request $request, string $reference, BookingSession $session): Response
    {
        $appointment = Appointment::query()->where('public_reference', $reference)->where('booking_owner_hash', hash('sha256', $session->secret($request)))->with('doctorDay.doctor.user')->firstOrFail();

        return response()->view('booking.confirmation', compact('appointment'), headers: ['Cache-Control' => 'no-store, private', 'X-Robots-Tag' => 'noindex, nofollow', 'Referrer-Policy' => 'same-origin']);
    }
}
