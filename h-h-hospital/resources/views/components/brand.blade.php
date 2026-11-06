@props(['light' => false])
@php($hospitalName = app(\App\Services\HospitalSettings::class)->current()['name'])
<a href="{{ route('home') }}" {{ $attributes->class(['brand', 'text-white' => $light]) }} aria-label="{{ $hospitalName }} — {{ __('ui.nav.public') }}">
    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/><path d="M12 12h8v8h-8z" fill="white" fill-opacity=".55"/></svg></span>
    <span><span class="brand-name text-base font-semibold tracking-tight">{{ $hospitalName }}</span><span class="block text-[10px] font-medium tracking-[.16em] uppercase opacity-60">{{ __('ui.brand.department') }}</span></span>
</a>
