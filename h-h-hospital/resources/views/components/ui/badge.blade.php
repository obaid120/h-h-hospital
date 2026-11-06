@props(['status' => 'pending', 'label' => null])
@php
    $tone = match ($status) { 'confirmed' => 'info', 'completed', 'active' => 'success', 'pending', 'priority' => 'warning', 'cancelled', 'full' => 'danger', default => 'neutral' };
    $translation = 'ui.status.'.$status;
    $label ??= \Illuminate\Support\Facades\Lang::has($translation) ? __($translation) : $status;
@endphp
<span {{ $attributes->class(['badge', 'badge-'.$tone]) }}><span class="badge-dot" aria-hidden="true"></span>{{ $label }}</span>
