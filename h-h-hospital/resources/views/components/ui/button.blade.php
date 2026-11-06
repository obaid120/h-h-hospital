@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'disabled' => false, 'loading' => false])
@php
    $classes = ['button', 'button-'.(in_array($variant, ['primary', 'secondary', 'ghost', 'danger']) ? $variant : 'primary'), 'button-sm' => $size === 'sm'];
    $unavailable = $disabled || $loading;
@endphp
@if($href)
    <a {{ $attributes->class($classes)->merge(['href' => $unavailable ? null : $href, 'aria-disabled' => $unavailable ? 'true' : null, 'aria-busy' => $loading ? 'true' : null, 'tabindex' => $unavailable ? '-1' : null]) }}>
        <span @class(['button-spinner', 'hidden' => !$loading]) data-button-spinner aria-hidden="true"></span><span data-button-label>{{ $slot }}</span>
    </a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $type, 'aria-busy' => $loading ? 'true' : null]) }} @disabled($unavailable)>
        <span @class(['button-spinner', 'hidden' => !$loading]) data-button-spinner aria-hidden="true"></span><span data-button-label>{{ $slot }}</span>
    </button>
@endif
