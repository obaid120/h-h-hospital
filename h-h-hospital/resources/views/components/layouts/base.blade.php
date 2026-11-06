@props(['title' => null, 'description' => null, 'printPage' => null])
@php($hospitalName = app(\App\Services\HospitalSettings::class)->current()['name'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if($printPage) class="{{ $printPage }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#126c60">
    <title>{{ $title ? $title.' · '.$hospitalName : $hospitalName }}</title>
    @if($description)<meta name="description" content="{{ $description }}">@endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body {{ $attributes->class(['antialiased']) }}>
    <a class="skip-link" href="#main-content">{{ __('ui.skip') }}</a>
    {{ $slot }}
    @stack('scripts')
</body>
</html>
