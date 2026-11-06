@props(['caption', 'headers' => []])
@php($hintId = 'table-hint-'.\Illuminate\Support\Str::uuid())
<div data-table-wrapper class="min-w-0"><p id="{{ $hintId }}" data-table-hint hidden data-screen-only class="border-b border-line bg-care-50 px-6 py-3 text-xs leading-5 text-care-800">{{ __('usability.table_hint') }}</p>
<div {{ $attributes->class(['table-scroll']) }} role="region" aria-label="{{ $caption }}" aria-describedby="{{ $hintId }}" tabindex="0">
    <table class="data-table">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach{{ $head ?? '' }}</tr></thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
</div>
