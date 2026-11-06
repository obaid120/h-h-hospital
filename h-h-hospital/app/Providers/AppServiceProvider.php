<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\DoctorPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserPolicy;
use App\Services\HospitalSettings;
use App\Services\PatientPhone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(HospitalSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TrustProxies::at(config('hosting.trusted_proxies'));

        Event::listen(DiagnosingHealth::class, function (): void {
            DB::select('SELECT 1');
        });

        Paginator::defaultView('pagination.hospital');
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Doctor::class, DoctorPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);

        $limited = fn (Request $request, array $headers): JsonResponse => response()->json([
            'error' => ['code' => 'rate_limited', 'message' => __('booking.errors.rate_limited')],
        ], 429, $headers + ['Cache-Control' => 'no-store, private']);

        RateLimiter::for('booking-read', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('hospital.booking.availability_requests_per_minute')))
            ->by('booking-read:'.hash('sha256', (string) $request->ip()))->response($limited));

        RateLimiter::for('public-booking', function (Request $request) use ($limited): array {
            $input = $request->input('phone');
            $phone = is_string($input) ? PatientPhone::normalize($input) : null;

            return [
                Limit::perMinute(max(1, (int) config('hospital.booking.requests_per_minute')))->by('booking-ip:'.hash('sha256', (string) $request->ip()))->response($limited),
                Limit::perMinute(max(1, (int) config('hospital.booking.requests_per_phone_per_minute')))->by('booking-phone:'.hash('sha256', $phone ?? (string) $request->ip()))->response($limited),
                Limit::perMinute(max(1, (int) config('hospital.booking.requests_per_session_per_minute')))->by('booking-session:'.hash('sha256', $request->session()->get('booking_owner', $request->session()->getId())))->response($limited),
            ];
        });
    }
}
