@props(['name', 'label', 'id' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'error' => null, 'errorBag' => 'default', 'required' => false, 'disabled' => false])
@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $oldInputKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $validationMessage = $error ?: $errors->getBag($errorBag)->first($oldInputKey);
    $selectedValue = old($oldInputKey, $value);
    $selectedValue = is_scalar($selectedValue) ? $selectedValue : '';
    $description = trim(($attributes->get('aria-describedby') ?? '').' '.($hint ? $id.'-hint' : '').' '.($validationMessage ? $id.'-error' : ''));
@endphp
<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :error="$validationMessage" :required="$required">
    <select {{ $attributes->class(['form-control', 'is-invalid' => (bool) $validationMessage])->merge(['id' => $id, 'name' => $name, 'aria-invalid' => $validationMessage ? 'true' : null, 'aria-describedby' => $description ?: null]) }} @required($required) @disabled($disabled)>
        @if($placeholder)<option value="" @selected($selectedValue === null || $selectedValue === '')>{{ $placeholder }}</option>@endif
        @foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) $selectedValue === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach
        {{ $slot }}
    </select>
</x-form.field>
