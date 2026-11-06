@php
    $rows = old('items', $prescription?->items->map(fn ($item) => $item->only(['medicine_name', 'dose', 'frequency', 'duration', 'instructions']))->all() ?? [[]]);
    $rows = is_array($rows) ? array_values(array_slice($rows, 0, 30)) : [[]];
    $saveKey = old('request_key', $requestKey);
    $saveKey = is_string($saveKey) ? $saveKey : $requestKey;
    $draftVersion = old('version', $prescription?->version ?? 0);
    $draftVersion = is_scalar($draftVersion) ? $draftVersion : ($prescription?->version ?? 0);
@endphp
<x-layouts.staff :title="__('prescription.edit')" :intro="__('prescription.intro')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.doctor.home')">{{ __('prescription.back') }}</x-ui.button></x-slot:actions>
    <section class="panel p-6"><div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="panel-title">{{ $prescription?->patient_name ?? $appointment->patient->name }}</h2><p class="mt-2 text-sm text-muted">{{ $prescription?->patient_mr ?? $appointment->patient->mr_number }} · {{ __('prescription.age') }}: {{ $appointment->verified_age }}</p><p class="mt-2 text-xs text-muted">{{ $appointment->doctorDay->doctor->user->name }} · {{ $appointment->doctorDay->date->format('d M Y') }} · {{ __('prescription.token') }} {{ $appointment->token_number }}</p></div><x-ui.badge status="pending" :label="__('prescription.draft')" /></div></section>
    <div data-prescription-workspace data-unsaved="{{ $errors->any() ? 'true' : 'false' }}" data-unsaved-text="{{ __('prescription.unsaved') }}" data-clean-text="{{ $prescription ? __('prescription.clean') : __('prescription.new') }}" data-limit-text="{{ __('prescription.row_limit') }}" data-row-label="{{ __('prescription.row', ['number' => '{number}']) }}" class="space-y-6">
        @if($errors->any())<div class="space-y-3"><x-ui.notification variant="error" :dismissible="false"><ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></x-ui.notification><p class="text-xs text-muted">{{ __('prescription.reload_hint') }}</p><a class="text-sm font-semibold text-care-700 underline" href="{{ route('staff.prescriptions.edit', $appointment) }}">{{ __('prescription.reload') }}</a></div>@endif
        <p data-prescription-feedback role="status" aria-live="polite" class="text-sm font-medium text-care-800"></p>
        <form method="POST" action="{{ route('staff.prescriptions.store', $appointment) }}" data-prescription-editor data-loading-form class="panel space-y-6 p-6 sm:p-8" novalidate>
            @csrf @method('PUT')<input type="hidden" name="request_key" value="{{ $saveKey }}"><input type="hidden" name="version" value="{{ $draftVersion }}">
            <x-form.textarea name="notes" :label="__('prescription.notes')" :value="$prescription?->notes" maxlength="10000" rows="5" />
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="panel-title">{{ __('prescription.medicines') }}</h2><button type="button" class="button button-secondary button-sm" data-add-medicine>{{ __('prescription.add') }}</button></div>
            <p class="text-xs leading-6 text-muted">{{ __('prescription.draft_hint') }}</p>
            <div data-medicine-list class="space-y-5">@foreach($rows as $index => $row)@include('staff.prescriptions.item', ['row' => is_array($row) ? $row : []])@endforeach</div>
            <x-form.textarea name="advice" :label="__('prescription.advice')" :value="$prescription?->advice" maxlength="5000" />
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6"><p class="text-xs text-muted">{{ $prescription ? __('prescription.saved_at').': '.$prescription->draft_saved_at->setTimezone($prescription->timezone)->format('d M Y H:i:s') : __('prescription.new') }}</p><x-ui.button type="submit">{{ __('prescription.save') }}</x-ui.button></div>
        </form>
        @if($prescription)
            <section class="panel space-y-5 p-6 sm:p-8"><div class="flex flex-wrap gap-3"><x-ui.button variant="secondary" :href="route('staff.prescriptions.print', $prescription)" target="_blank" rel="noopener" data-prescription-saved-action>{{ __('prescription.print') }}</x-ui.button><x-ui.button variant="secondary" :href="route('staff.prescriptions.show', $prescription)" target="_blank" rel="noopener">{{ __('prescription.view') }}</x-ui.button></div><p class="text-xs leading-6 text-muted">{{ __('prescription.print_hint') }}</p>
                <form method="POST" action="{{ route('staff.prescriptions.complete', $appointment) }}" data-prescription-complete data-loading-form class="space-y-5 border-t border-line pt-5">@csrf<input type="hidden" name="version" value="{{ $prescription->version }}"><input type="hidden" name="completion_key" value="{{ $completionKey }}"><h2 class="panel-title">{{ __('prescription.complete') }}</h2><p class="text-xs leading-6 text-muted">{{ __('prescription.complete_hint') }}</p><label class="flex items-start gap-3 text-sm leading-6"><input type="checkbox" name="completion_confirmed" value="1" class="mt-1 size-4 shrink-0 accent-care-700" required>{{ __('prescription.complete_confirmed') }}</label><x-ui.button type="submit" data-prescription-saved-action>{{ __('prescription.complete') }}</x-ui.button></form>
            </section>
        @endif
        <template data-medicine-template>@include('staff.prescriptions.item', ['index' => 0, 'row' => []])</template>
    </div>
</x-layouts.staff>
