@props(['name', 'label', 'id' => null, 'value' => null, 'hint' => null, 'error' => null, 'errorBag' => 'default', 'required' => false, 'disabled' => false])
@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $validationMessage = $error ?: $errors->getBag($errorBag)->first($name);
    $description = trim(($attributes->get('aria-describedby') ?? '').' '.($hint ? $id.'-hint' : '').' '.($validationMessage ? $id.'-error' : ''));
    $inputValue = old($name, $value);
    $inputValue = is_scalar($inputValue) ? $inputValue : '';
@endphp
<x-form.field :name="$name" :id="$id" :label="$label" :hint="$hint" :error="$validationMessage" :required="$required">
    <textarea {{ $attributes->class(['form-control', 'is-invalid' => (bool) $validationMessage])->merge(['id' => $id, 'name' => $name, 'rows' => 4, 'aria-invalid' => $validationMessage ? 'true' : null, 'aria-describedby' => $description ?: null]) }} @required($required) @disabled($disabled)>{{ $inputValue }}</textarea>
</x-form.field>
