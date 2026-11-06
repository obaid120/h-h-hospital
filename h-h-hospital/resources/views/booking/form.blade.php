<form method="POST" action="{{ route('booking.form.store', $doctor) }}" data-booking-form data-submit-url="{{ route('booking.store') }}" data-month-url="{{ route('booking.month', $doctor) }}" data-form-url="{{ route('booking.create', $doctor) }}" data-uncertain="{{ session('booking_uncertain') ? 'true' : 'false' }}" class="space-y-6" novalidate>
    @csrf
    <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
    <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::isUuid(old('request_key')) ? old('request_key') : $requestKey }}">
    <script type="application/json" data-calendar-initial>@json($calendar)</script>
    <script type="application/json" data-booking-labels>@json(__('booking'))</script>
    <section class="flex items-center gap-4 rounded-xl border border-care-100 bg-care-50/50 p-4"><x-doctor-photo :doctor="$doctor" class="size-16 text-lg" /><div class="min-w-0"><h3 class="text-base font-semibold break-words" tabindex="-1" data-booking-focus>{{ $doctor->user->name }}</h3><p class="mt-1 text-xs text-care-700">{{ $doctor->specialization }}</p><p class="mt-2 text-sm font-semibold">{{ \App\Models\Doctor::feeDisplay($doctor->normal_fee_minor) }}</p></div></section>
    <div data-booking-feedback role="alert" tabindex="-1" class="notification notification-error" @if(!$errors->any()) hidden @endif>{{ $errors->first('booking') ?: $errors->first() }}</div>
    <div class="grid items-start gap-7 md:grid-cols-2">
        <section class="min-w-0 space-y-5"><h3 class="panel-title">{{ __('booking.calendar_title') }}</h3>
            <div data-booking-calendar hidden class="booking-calendar"><div class="flex items-center justify-between gap-3"><button type="button" class="icon-button" data-month-step="-1" aria-label="{{ __('booking.previous') }}"><x-icon name="arrow-left" class="size-4" /></button><h4 data-calendar-month class="text-sm font-semibold" aria-live="polite"></h4><button type="button" class="icon-button" data-month-step="1" aria-label="{{ __('booking.next') }}"><x-icon name="arrow-right" class="size-4" /></button></div>
                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[10px] font-semibold text-muted" aria-hidden="true">@foreach(__('booking.weekdays') as $weekday)<span class="py-2">{{ $weekday }}</span>@endforeach</div>
                <div data-calendar-days class="grid grid-cols-7 gap-1" role="group" aria-label="{{ __('booking.calendar_title') }}"></div>
                <p data-calendar-feedback class="mt-3 text-xs leading-5 text-muted" role="status" aria-live="polite"></p>
                <div class="mt-4 flex flex-wrap gap-3 text-[10px] text-muted"><span><span class="mr-1 inline-block size-2 rounded-full bg-care-600"></span>{{ __('booking.states.available') }}</span><span>{{ __('booking.states.full') }}</span><span>{{ __('booking.states.closed') }}</span></div>
                <button type="button" data-calendar-retry class="mt-4 text-xs font-semibold text-care-700">{{ __('booking.retry_calendar') }}</button>
            </div>
            <div data-date-fallback><x-form.input name="date" id="booking-date" type="date" :label="__('booking.date')" :hint="__('booking.date_hint')" :min="$calendar['today']" :max="$calendar['last_date']" :required="true" /></div>
            <p data-selected-date class="rounded-xl border border-line bg-canvas p-4 text-xs leading-6" role="status" aria-live="polite">{{ __('booking.select_date') }}</p>
            <p id="booking-date-client-error" data-booking-error="date" class="field-error" hidden></p>
            <p class="text-[11px] leading-5 text-muted">{{ __('booking.local_times', ['timezone' => $calendar['timezone']]) }}</p>
        </section>
        <section class="min-w-0 space-y-5"><h3 class="panel-title">{{ __('booking.patient_details') }}</h3>
            <div><x-form.input name="name" id="booking-name" :label="__('booking.name')" maxlength="100" autocomplete="name" aria-describedby="booking-name-client-error" :required="true" /><p id="booking-name-client-error" data-booking-error="name" class="field-error" hidden></p></div>
            <div><x-form.input name="phone" id="booking-phone" type="tel" :label="__('booking.phone')" :hint="__('patients.phone_hint')" maxlength="40" autocomplete="tel" aria-describedby="booking-phone-client-error" :required="true" /><p id="booking-phone-client-error" data-booking-error="phone" class="field-error" hidden></p></div>
            <div><x-form.input name="age_years" id="booking-age" type="number" :label="__('booking.age')" min="0" max="130" step="1" inputmode="numeric" aria-describedby="booking-age-client-error" :required="true" /><p id="booking-age-client-error" data-booking-error="age_years" class="field-error" hidden></p></div>
            <p class="text-xs leading-6 text-muted">{{ __('booking.fee_note') }}</p>
        </section>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-5 border-t border-line pt-5"><p class="max-w-lg text-xs leading-6 text-muted">{{ __('booking.arrival_note') }}</p><x-ui.button type="submit" class="w-full sm:w-auto" data-booking-submit>{{ __('booking.submit') }}</x-ui.button></div>
</form>
