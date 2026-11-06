<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AdminReports
{
    public function appointments(array $filters): Builder
    {
        return Appointment::query()->whereHas('doctorDay', function (Builder $days) use ($filters): void {
            $days->where('date', $filters['date']);
            if (! empty($filters['doctor_id'])) {
                $days->where('doctor_id', $filters['doctor_id']);
            }
        })->when($filters['source'] ?? null, fn (Builder $query, string $source): Builder => $query->where('source', $source));
    }

    public function dayBounds(string $date): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $date, config('hospital.timezone'));

        return [$start->utc()->format('Y-m-d H:i:s'), $start->addDay()->utc()->format('Y-m-d H:i:s')];
    }

    public function collections(array $filters): Builder
    {
        [$start, $end] = $this->dayBounds($filters['date']);

        return Payment::query()->where('paid_at', '>=', $start)->where('paid_at', '<', $end)
            ->when($filters['cashier_id'] ?? null, fn (Builder $query, int $cashier): Builder => $query->where('cashier_id', $cashier));
    }

    public function refunds(array $filters): Builder
    {
        [$start, $end] = $this->dayBounds($filters['date']);

        return PaymentRefund::query()->where('refunded_at', '>=', $start)->where('refunded_at', '<', $end)
            ->when($filters['cashier_id'] ?? null, fn (Builder $query, int $cashier): Builder => $query->whereHas('payment', fn (Builder $payments): Builder => $payments->where('cashier_id', $cashier)));
    }

    public function money(array $filters): array
    {
        $collections = $this->collections($filters)->selectRaw('currency, SUM(total_minor) AS amount')->groupBy('currency')->pluck('amount', 'currency');
        $refunds = $this->refunds($filters)->selectRaw('currency, SUM(amount_minor) AS amount')->groupBy('currency')->pluck('amount', 'currency');
        $currencies = $collections->keys()->merge($refunds->keys())->unique()->sort();
        if ($currencies->isEmpty()) {
            $currencies = collect([config('hospital.currency')]);
        }

        return $currencies->mapWithKeys(fn (string $currency): array => [$currency => ['collected' => (int) ($collections[$currency] ?? 0), 'refunded' => (int) ($refunds[$currency] ?? 0), 'net' => (int) ($collections[$currency] ?? 0) - (int) ($refunds[$currency] ?? 0)]])->all();
    }
}
