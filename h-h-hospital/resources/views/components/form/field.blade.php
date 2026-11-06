@props(['name', 'id', 'label', 'hint' => null, 'error' => null, 'required' => false])
<div {{ $attributes->class(['field']) }}>
    <label for="{{ $id }}" class="field-label">{{ $label }}@if($required)<span class="text-care-700" aria-hidden="true"> *</span><span class="sr-only"> ({{ __('ui.required') }})</span>@endif</label>
    {{ $slot }}
    @if($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
    <x-form.error :message="$error" :id="$id.'-error'" />
</div>
