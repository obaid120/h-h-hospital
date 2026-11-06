<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->text('print_footer')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 100)->unique();
            $table->string('action', 50)->index();
            $table->string('record_type', 30);
            $table->unsignedBigInteger('record_id');
            $table->index(['record_type', 'record_id']);
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name', 100);
            $table->string('actor_role', 20);
            $table->json('before_values');
            $table->json('after_values');
            $table->string('currency', 3);
            $table->timestamp('occurred_at')->index();
            $table->string('timezone', 64);
            $table->boolean('historical')->default(false);
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('hospital_name', 120)->nullable();
            $table->text('hospital_address')->nullable();
            $table->string('hospital_phone', 40)->nullable();
            $table->text('hospital_footer')->nullable();
            $table->index(['paid_at', 'cashier_id']);
        });
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->text('hospital_footer')->nullable();
        });
        Schema::table('payment_refunds', function (Blueprint $table): void {
            $table->index(['refunded_at', 'payment_id']);
        });
        DB::table('payments')->update(['hospital_name' => config('app.name'), 'hospital_address' => config('hospital.contact.address'), 'hospital_phone' => config('hospital.contact.phone')]);
        $this->retainSavedHistory();
    }

    private function retainSavedHistory(): void
    {
        DB::table('payments')->orderBy('id')->chunkById(200, function (Collection $payments): void {
            foreach ($payments as $payment) {
                $visit = DB::table('appointments')->find($payment->appointment_id);
                $doctorId = DB::table('doctor_days')->where('id', $visit->doctor_day_id)->value('doctor_id');
                $base = ['doctor_id' => $doctorId, 'actor_id' => $payment->cashier_id, 'actor_name' => $payment->cashier_name, 'actor_role' => 'receptionist', 'currency' => $payment->currency, 'occurred_at' => $payment->paid_at, 'timezone' => $payment->timezone];
                $this->history($base, 'payment:'.$payment->id, 'payment.collected', 'payment', $payment->id, [], ['receipt_number' => $payment->receipt_number, 'total_minor' => $payment->total_minor, 'token_number' => $payment->token_number]);
                $this->history($base, 'confirmation:'.$visit->id, 'appointment.confirmed', 'appointment', $visit->id, ['status' => 'pending'], ['status' => 'confirmed', 'source' => $visit->source, 'token_number' => $payment->token_number]);
                if ($payment->is_priority) {
                    $this->history($base, 'priority:'.$visit->id, 'appointment.priority', 'appointment', $visit->id, [], ['is_priority' => true, 'priority_surcharge_minor' => $payment->priority_charge_minor]);
                }
            }
        });
        DB::table('appointment_cancellations')->orderBy('id')->chunkById(200, function (Collection $events): void {
            foreach ($events as $event) {
                $visit = DB::table('appointments')->find($event->appointment_id);
                $base = ['doctor_id' => DB::table('doctor_days')->where('id', $visit->doctor_day_id)->value('doctor_id'), 'actor_id' => $event->actor_id, 'actor_name' => $event->actor_name, 'actor_role' => $event->actor_role, 'currency' => config('hospital.currency'), 'occurred_at' => $event->cancelled_at, 'timezone' => $event->timezone];
                $this->history($base, 'cancellation:'.$event->id, 'appointment.cancelled', 'appointment', $visit->id, ['status' => $event->previous_status], ['status' => 'cancelled', 'reason' => $event->reason, 'released_capacity' => $event->released_capacity]);
            }
        });
        DB::table('payment_refunds')->orderBy('id')->chunkById(200, function (Collection $refunds): void {
            foreach ($refunds as $refund) {
                $payment = DB::table('payments')->find($refund->payment_id);
                $visit = DB::table('appointments')->find($payment->appointment_id);
                $base = ['doctor_id' => DB::table('doctor_days')->where('id', $visit->doctor_day_id)->value('doctor_id'), 'actor_id' => $refund->actor_id, 'actor_name' => $refund->actor_name, 'actor_role' => $refund->actor_role, 'currency' => $refund->currency, 'occurred_at' => $refund->refunded_at, 'timezone' => $refund->timezone];
                $this->history($base, 'refund:'.$refund->id, 'payment.refunded', 'payment', $refund->payment_id, [], ['refund_reference' => $refund->refund_reference, 'amount_minor' => $refund->amount_minor, 'reason' => $refund->reason]);
            }
        });
        DB::table('prescriptions')->whereNotNull('finalized_at')->orderBy('id')->chunkById(200, function (Collection $prescriptions): void {
            foreach ($prescriptions as $prescription) {
                $base = ['doctor_id' => $prescription->doctor_id, 'actor_id' => $prescription->finalized_by, 'actor_name' => $prescription->doctor_name, 'actor_role' => 'doctor', 'currency' => config('hospital.currency'), 'occurred_at' => $prescription->finalized_at, 'timezone' => $prescription->timezone];
                $this->history($base, 'completion:'.$prescription->id, 'appointment.completed', 'appointment', $prescription->appointment_id, ['status' => 'confirmed'], ['status' => 'completed', 'prescription_id' => $prescription->id]);
            }
        });
    }

    private function history(array $base, string $key, string $action, string $type, int $id, array $before, array $after): void
    {
        DB::table('audit_logs')->insert($base + ['event_key' => $key, 'action' => $action, 'record_type' => $type, 'record_id' => $id, 'before_values' => json_encode($before, JSON_THROW_ON_ERROR), 'after_values' => json_encode($after, JSON_THROW_ON_ERROR), 'historical' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('payment_refunds', function (Blueprint $table): void {
            $table->dropIndex(['refunded_at', 'payment_id']);
        });
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropColumn('hospital_footer');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['paid_at', 'cashier_id']);
            $table->dropColumn(['hospital_name', 'hospital_address', 'hospital_phone', 'hospital_footer']);
        });
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('hospital_settings');
    }
};
