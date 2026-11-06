@php
    $key = old('request_key', $requestKey);
    $key = is_string($key) ? $key : $requestKey;
@endphp
<input type="hidden" name="request_key" value="{{ $key }}">
@if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ $errors->first() }}</x-ui.notification>@endif
<fieldset class="space-y-5"><legend class="panel-title">{{ __('reception.identity') }}</legend>
    <p class="text-xs leading-6 text-muted">{{ __('reception.mode_hint') }}</p>
    <x-form.select name="patient_mode" :label="__('reception.identity')" :options="['existing' => __('reception.existing'), 'new' => __('reception.new')]" :value="'existing'" :required="true" />
    @if($matches->isEmpty())<p class="text-xs text-muted">{{ __('reception.no_matches') }}</p>@endif
    <x-form.select name="patient_id" :label="__('reception.choose')" :value="$selectedPatientId ?? ''" :placeholder="__('reception.choose')" :options="$matches->mapWithKeys(fn ($match) => [$match->id => $match->name.' · '.$match->mr_number.' · '.$match->age_years.' years · '.$match->phone])" :hint="__('reception.other_mr')" />
    <div class="grid gap-5 sm:grid-cols-2"><x-form.input name="name" :label="__('patients.name').' — '.__('reception.new')" :value="$submittedName ?? ''" maxlength="100" /><x-form.input name="phone" type="tel" :label="__('patients.phone').' — '.__('reception.new')" :value="$phone ?? ''" :hint="__('patients.phone_hint')" maxlength="40" /></div>
    <x-form.input name="age_years" type="number" :label="__('reception.age')" :value="$submittedAge ?? ''" min="0" max="130" step="1" :required="true" />
    @foreach(['identity_confirmed', 'cash_received'] as $check)<div><label class="flex items-start gap-3 text-sm leading-6" for="{{ $check }}"><input type="checkbox" id="{{ $check }}" name="{{ $check }}" value="1" class="mt-1 size-4 shrink-0 accent-care-700" @checked(old($check)) aria-describedby="{{ $check }}-error">{{ __('reception.'.$check) }}</label><x-form.error :id="$check.'-error'" :message="$errors->first($check)" /></div>@endforeach
</fieldset>
