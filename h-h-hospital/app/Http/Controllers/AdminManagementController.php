<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AdminReports;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    public function appointments(Request $request, AdminReports $reports): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'], 'doctor_id' => ['nullable', 'integer', Rule::exists(Doctor::class, 'id')],
            'source' => ['nullable', Rule::enum(AppointmentSource::class)], 'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
        ]);
        $filters['date'] ??= now(config('hospital.timezone'))->toDateString();
        $query = $reports->appointments($filters);
        $counts = (clone $query)->selectRaw('status, COUNT(*) AS count')->groupBy('status')->pluck('count', 'status');
        $appointments = $query->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->with(['doctorDay.doctor.user', 'patient', 'payment'])->orderByDesc('id')->paginate(20)->withQueryString();
        $doctors = Doctor::query()->with('user')->orderBy('id')->get();

        return view('staff.admin.appointments', compact('filters', 'counts', 'appointments', 'doctors'));
    }

    public function finances(Request $request, AdminReports $reports): View
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'cashier_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')]]);
        $filters['date'] ??= now(config('hospital.timezone'))->toDateString();
        $totals = $reports->money($filters);
        $collections = $reports->collections($filters)->orderByDesc('paid_at')->orderByDesc('id')->paginate(15, ['*'], 'collections_page')->withQueryString();
        $refunds = $reports->refunds($filters)->with('payment')->orderByDesc('refunded_at')->orderByDesc('id')->paginate(15, ['*'], 'refunds_page')->withQueryString();
        $cashiers = User::query()->whereHas('payments')->orderBy('name')->get();

        return view('staff.admin.finances', compact('filters', 'totals', 'collections', 'refunds', 'cashiers'));
    }

    public function activity(Request $request, AdminReports $reports): View
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'doctor_id' => ['nullable', 'integer', Rule::exists(Doctor::class, 'id')], 'actor_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')], 'action' => ['nullable', Rule::in(array_keys(__('admin.actions')))]]);
        $filters['date'] ??= now(config('hospital.timezone'))->toDateString();
        [$start, $end] = $reports->dayBounds($filters['date']);
        $entries = AuditLog::query()->where('occurred_at', '>=', $start)->where('occurred_at', '<', $end);
        foreach (['doctor_id', 'actor_id', 'action'] as $field) {
            if (! empty($filters[$field])) {
                $entries->where($field, $filters[$field]);
            }
        }
        $entries = $entries->orderByDesc('occurred_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $doctors = Doctor::query()->with('user')->orderBy('id')->get();
        $actors = User::query()->whereIn('id', AuditLog::query()->select('actor_id'))->orderBy('name')->get();

        return view('staff.admin.activity', compact('filters', 'entries', 'doctors', 'actors'));
    }
}
