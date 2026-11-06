<div class="my-7 space-y-6" data-screen-only>
    @include('staff.reception.financial-summary')
    <div class="flex flex-wrap items-center gap-3"><x-ui.badge :status="$payment->appointment->status->value" /><p class="text-xs text-muted">{{ $payment->receipt_number }}</p></div>
    @if($payment->appointment->cancellation)@include('staff.reception.cancellation-history', ['cancellation' => $payment->appointment->cancellation])@endif
    @if($refunds->isNotEmpty())
        <section class="panel overflow-hidden">
            <h2 class="panel-title p-6">{{ __('cancellations.refund_history') }}</h2>
            <ol class="divide-y divide-line" role="list">
                @foreach($refunds as $refund)
                    <li class="space-y-5 p-6">
                        <dl class="grid gap-5 text-xs sm:grid-cols-2">
                            <div class="min-w-0"><dt class="text-muted">{{ __('cancellations.refund_reference') }}</dt><dd class="mt-2 break-all tabular-nums">{{ $refund->refund_reference }}</dd></div>
                            <div><dt class="text-muted">{{ __('cancellations.amount') }}</dt><dd class="mt-2 text-sm font-semibold">{{ $payment->amount($refund->amount_minor) }}</dd></div>
                            <div><dt class="text-muted">{{ __('cancellations.refunded_at') }}</dt><dd class="mt-2">{{ $refund->refunded_at->setTimezone($refund->timezone)->format('d M Y H:i:s') }} · {{ $refund->timezone }}</dd></div>
                            <div class="min-w-0"><dt class="text-muted">{{ __('cancellations.actor') }}</dt><dd class="mt-2 break-words">{{ $refund->actor_name }} · {{ $refund->actor_role->label() }}</dd></div>
                        </dl>
                        <div class="text-xs"><p class="text-muted">{{ __('cancellations.reason') }}</p><p class="mt-2 break-words leading-6">{{ $refund->reason }}</p></div>
                    </li>
                @endforeach
            </ol>
            {{ $refunds->links() }}
        </section>
    @endif
    @if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ $errors->first() }}</x-ui.notification>@endif
    @if($payment->appointment->status === \App\Enums\AppointmentStatus::Cancelled && $payment->appointment->cancellation && $payment->total_minor > $refundedMinor)
        @can('refund', $payment)
            @php($formKey = is_string(old('request_key', $refundKey)) ? old('request_key', $refundKey) : $refundKey)
            <form method="POST" action="{{ route('staff.payments.refunds.store', $payment) }}" class="panel space-y-5 p-6" data-loading-form data-retry-form data-session-url="{{ route('staff.session') }}" data-actor-id="{{ auth()->id() }}" novalidate>@csrf @include('staff.reception.retry-feedback')<input type="hidden" name="request_key" value="{{ $formKey }}"><h2 class="panel-title">{{ __('cancellations.refund') }}</h2><p class="text-xs leading-6 text-muted">{{ __('cancellations.refund_hint') }}</p><x-form.input name="amount" :label="__('cancellations.amount').' ('.$payment->currency.')'" inputmode="decimal" maxlength="10" :hint="__('cancellations.amount_hint', ['currency' => $payment->currency])" :required="true" /><x-form.textarea name="reason" :label="__('cancellations.reason')" maxlength="1000" :required="true" /><label class="flex items-start gap-3 text-sm leading-6"><input type="checkbox" name="cash_returned" value="1" class="mt-1 size-4 shrink-0 accent-care-700" @checked(old('cash_returned'))>{{ __('cancellations.cash_returned') }}</label><x-form.error :message="$errors->first('cash_returned')" /><x-ui.button type="submit">{{ __('cancellations.refund') }}</x-ui.button></form>
        @else<p class="text-xs text-muted">{{ __('cancellations.admin_only') }}</p>@endcan
    @elseif($payment->appointment->status !== \App\Enums\AppointmentStatus::Cancelled)<p class="text-xs text-muted">{{ __('cancellations.cancel_first') }}</p>@endif
</div>
