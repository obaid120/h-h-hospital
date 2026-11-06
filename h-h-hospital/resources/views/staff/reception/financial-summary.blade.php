@php($remainingMinor = max(0, $payment->total_minor - $refundedMinor))
<section class="panel space-y-5 p-6" data-screen-only>
    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="panel-title">{{ __('cancellations.financial') }}</h2><x-ui.badge :status="$refundedMinor === 0 ? 'pending' : ($remainingMinor === 0 ? 'completed' : 'confirmed')" :label="$refundedMinor === 0 ? __('cancellations.no_refund') : ($remainingMinor === 0 ? __('cancellations.full_refund') : __('cancellations.partial_refund'))" /></div>
    <dl class="grid gap-5 text-sm sm:grid-cols-3"><div><dt class="text-xs text-muted">{{ __('cancellations.collection') }}</dt><dd class="mt-2 font-semibold">{{ $payment->amount($payment->total_minor) }}</dd></div><div><dt class="text-xs text-muted">{{ __('cancellations.refunded') }}</dt><dd class="mt-2 font-semibold">{{ $payment->amount($refundedMinor) }}</dd></div><div><dt class="text-xs text-muted">{{ __('cancellations.remaining') }}</dt><dd class="mt-2 font-semibold">{{ $payment->amount($remainingMinor) }}</dd></div></dl>
    <p class="text-xs leading-6 text-muted">{{ __('cancellations.independent') }}</p>
</section>
