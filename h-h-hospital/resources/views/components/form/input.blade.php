@props(['name', 'label', 'id' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'error' => null, 'errorBag' => 'default', 'required' => false, 'disabled' => false])
@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $oldInputKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $inputValue = $type === 'password' ? '' : old($oldInputKey, $value);
    $inputValue = is_scalar($inputValue) || $inputValue instanceof \Stringable ? $inputValue : '';
    $validationMessage = $error ?: $errors->getBag($errorBag)->first($oldInputKey);
    $description = trim(($attributes->get('aria-describedby') ?? '').' '.($hint ? $id.'-hint' : '').' '.($validationMessage ? $id.'-error' : ''));
@endphp
<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :error="$validationMessage" :required="$required">
    <input {{ $attributes->class(['form-control', 'is-invalid' => (bool) $validationMessage])->merge(['id' => $id, 'name' => $name, 'type' => $type, 'aria-invalid' => $validationMessage ? 'true' : null, 'aria-describedby' => $description ?: null]) }} @if($type !== 'file') value="{{ $inputValue }}" @endif @required($required) @disabled($disabled)>
</x-form.field>
