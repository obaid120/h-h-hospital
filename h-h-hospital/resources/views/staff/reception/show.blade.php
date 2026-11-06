<x-layouts.staff :title="$appointment->public_reference" :intro="__('reception.submitted')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.appointments.index')">{{ __('reception.back') }}</x-ui.button></x-slot:actions>
    <section class="panel space-y-5 p-6 sm:p-8"><div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="panel-title">{{ $appointment->patient?->name ?? $appointment->submitted_name }}</h2><p class="mt-2 text-sm text-muted">{{ $appointment->submitted_phone }} · {{ $appointment->submitted_age }} years</p>@if($appointment->patient)<p class="mt-2 text-sm">{{ $appointment->patient->mr_number }}</p>@endif</div><x-ui.badge :status="$appointment->status->value" /></div>
        <div class="grid gap-5 text-sm sm:grid-cols-3"><div><p class="text-xs text-muted">{{ __('reception.doctor') }}</p><p class="mt-2 font-semibold">{{ $appointment->doctorDay->doctor->user->name }}</p></div><div><p class="text-xs text-muted">{{ __('reception.date') }}</p><p class="mt-2 font-semibold">{{ $appointment->doctorDay->date->format('d M Y') }}</p><p class="mt-1 text-xs text-muted">{{ substr($appointment->opd_opens_at ?? '', 0, 5) }}–{{ substr($appointment->opd_closes_at ?? '', 0, 5) }} · {{ config('hospital.timezone') }}</p></div><div><p class="text-xs text-muted">{{ __('reception.fee') }}</p><p class="mt-2 font-semibold">{{ \App\Models\Doctor::feeDisplay($appointment->normal_fee_minor) }}</p></div></div>
        @if($appointment->payment)<x-ui.button :href="route('staff.receipts.show', $appointment->payment)">{{ __('reception.view_receipt') }}</x-ui.button>
        @elseif(!$canArrive)<x-ui.notification :dismissible="false">{{ __('reception.same_day') }}</x-ui.notification>@endif
    </section>
    @if($appointment->cancellation)@include('staff.reception.cancellation-history', ['cancellation' => $appointment->cancellation])@endif
    @if($appointment->payment)@include('staff.reception.financial-summary', ['payment' => $appointment->payment])@endif
    @can('cancel', $appointment)
        @if($canCancel)
            @php($formKey = is_string(old('request_key', $cancellationKey)) ? old('request_key', $cancellationKey) : $cancellationKey)
            <form method="POST" action="{{ route('staff.appointments.cancel', $appointment) }}" class="panel max-w-3xl space-y-5 p-6" data-loading-form data-retry-form data-session-url="{{ route('staff.session') }}" data-actor-id="{{ auth()->id() }}" novalidate>@csrf @include('staff.reception.retry-feedback')<input type="hidden" name="request_key" value="{{ $formKey }}"><h2 class="panel-title">{{ __('cancellations.cancel') }}</h2><p class="text-xs leading-6 text-muted">{{ __('cancellations.cancel_hint') }}</p>@if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ $errors->first() }}</x-ui.notification>@endif<x-form.textarea name="reason" :label="__('cancellations.reason')" maxlength="1000" :required="true" /><label class="flex items-start gap-3 text-sm leading-6"><input type="checkbox" name="cancellation_confirmed" value="1" class="mt-1 size-4 shrink-0 accent-care-700" @checked(old('cancellation_confirmed'))>{{ __('cancellations.cancel_confirmed') }}</label><x-form.error :message="$errors->first('cancellation_confirmed')" /><x-ui.button type="submit" variant="danger">{{ __('cancellations.cancel') }}</x-ui.button></form>
        @elseif(!$appointment->cancellation && $appointment->status !== \App\Enums\AppointmentStatus::Pending)<p class="text-xs text-muted">{{ __('cancellations.not_eligible') }}</p>@endif
    @endcan
    @can('confirm', $appointment)
        @if($canArrive && !$appointment->payment)
            <section class="panel max-w-3xl p-6"><form method="GET" action="{{ route('staff.appointments.show', $appointment) }}" class="flex flex-wrap items-end gap-4"><div class="min-w-0 flex-1"><x-form.input name="q" :label="__('reception.patient_query')" :value="$lookup" maxlength="100" /></div><x-ui.button type="submit" variant="secondary">{{ __('reception.lookup_patient') }}</x-ui.button></form></section>
            <form method="POST" action="{{ route('staff.reception.confirm', $appointment) }}" class="panel max-w-3xl space-y-6 p-6 sm:p-8" data-loading-form data-retry-form data-session-url="{{ route('staff.session') }}" data-actor-id="{{ auth()->id() }}" novalidate>
                @csrf @include('staff.reception.retry-feedback')<h2 class="panel-title">{{ __('reception.arrival') }}</h2>
                <x-ui.notification variant="warning" :dismissible="false">{{ __('reception.unverified') }} {{ __('reception.reserved_hint') }}</x-ui.notification>
                @include('staff.reception.identity', ['phone' => $appointment->submitted_phone, 'submittedName' => $appointment->submitted_name, 'submittedAge' => $appointment->submitted_age])
                <div class="border-t border-line pt-6"><p class="mb-4 text-sm font-semibold">{{ __('reception.fee') }}: {{ \App\Models\Doctor::feeDisplay($appointment->normal_fee_minor) }}</p><x-ui.button type="submit">{{ __('reception.confirm') }}</x-ui.button></div>
            </form>
        @endif
    @endcan
</x-layouts.staff>
