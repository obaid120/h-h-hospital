<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\StaffRole;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentRefundService
{
    public function record(User $actor, Payment $payment, array $input): PaymentRefund
    {
        $data = Validator::make(CancellationRefundInput::normalize($input), CancellationRefundInput::rules(refund: true))->validate();
        $minor = CancellationRefundInput::minorUnits($data['amount']);
        if ($minor < 1) {
            $this->reject('positive_amount');
        }
        $hash = CancellationRefundInput::fingerprint($payment->id, $data, refund: true);
        $anchor = Payment::query()->with('appointment.doctorDay')->findOrFail($payment->id);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($actor, $data, $minor, $hash, $anchor): PaymentRefund {
                    $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
                    abort_unless($fresh->is_active && $fresh->role === StaffRole::Admin && $fresh->auth_version === $actor->auth_version, 403);
                    Doctor::query()->lockForUpdate()->findOrFail($anchor->appointment->doctorDay->doctor_id);
                    $day = DoctorDay::query()->lockForUpdate()->findOrFail($anchor->appointment->doctor_day_id);
                    $visit = Appointment::query()->lockForUpdate()->findOrFail($anchor->appointment_id);
                    $collection = Payment::query()->lockForUpdate()->findOrFail($anchor->id);
                    abort_unless($visit->doctor_day_id === $day->id && $collection->appointment_id === $visit->id, 403);
                    $existing = PaymentRefund::query()->where('request_key', $data['request_key'])->first();
                    if ($existing) {
                        if (! hash_equals($existing->payload_hash, $hash)) {
                            $this->reject('key_conflict');
                        }

                        return $existing;
                    }
                    if ($visit->status !== AppointmentStatus::Cancelled || ! $visit->cancellation()->exists() || $visit->consultation_started_at || $visit->completed_at || $day->current_appointment_id === $visit->id) {
                        $this->reject('cancel_before_refund');
                    }
                    $refunded = (int) $collection->refunds()->sum('amount_minor');
                    if ($minor > $collection->total_minor - $refunded) {
                        $this->reject('over_refund');
                    }

                    $refund = PaymentRefund::create([
                        'payment_id' => $collection->id, 'refund_reference' => app(RefundReference::class)->generate(),
                        'request_key' => $data['request_key'], 'payload_hash' => $hash, 'amount_minor' => $minor,
                        'currency' => $collection->currency, 'method' => 'cash', 'reason' => $data['reason'],
                        'actor_id' => $fresh->id, 'actor_name' => $fresh->name, 'actor_role' => $fresh->role,
                        'refunded_at' => now(), 'timezone' => config('hospital.timezone'),
                    ]);
                    app(ActivityRecorder::class)->record($fresh, 'payment.refunded', 'payment', $collection->id, [], ['refund_reference' => $refund->refund_reference, 'amount_minor' => $refund->amount_minor, 'reason' => $refund->reason], $day->doctor_id, 'refund:'.$refund->id, $collection->currency);

                    return $refund;
                }, attempts: 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Refund retries exhausted.');
    }

    private function reject(string $reason): never
    {
        throw ValidationException::withMessages(['refund' => __('cancellations.errors.'.$reason)]);
    }
}
