@props(['title' => null, 'description' => null])
@php($hospital = app(\App\Services\HospitalSettings::class)->current())
<x-layouts.base :title="$title" :description="$description">
    <header class="relative z-20 border-b border-line bg-white/80" data-screen-only>
        <div class="page-container flex min-h-24 items-center justify-between gap-6">
            <x-brand />
            <nav aria-label="{{ __('ui.nav.public') }}" class="hidden items-center gap-8 lg:flex">
                <a class="public-nav-link" href="{{ route('home') }}#doctors">{{ __('booking.choose_doctor') }}</a>
                <a class="public-nav-link" href="{{ route('home') }}#your-visit">{{ __('ui.nav.visit') }}</a>
                <a class="public-nav-link" href="{{ route('home') }}#visiting-information">{{ __('ui.nav.information') }}</a>
                <a class="public-nav-link" href="{{ route('home') }}#contact">{{ __('ui.nav.contact') }}</a>
            </nav>
            <div class="hidden lg:block">
                <x-ui.button variant="secondary" :href="auth()->check() ? route('staff.home') : route('staff.login')">{{ auth()->check() ? __('staff.nav.workspace') : __('staff.nav.login') }} <x-icon name="arrow-right" class="size-4" /></x-ui.button>
            </div>
            <details class="public-menu lg:hidden">
                <summary class="icon-button" aria-label="{{ __('ui.nav.menu') }}"><x-icon name="menu" /></summary>
                <nav class="absolute top-full right-6 left-6 mt-2 space-y-1 rounded-2xl border border-line bg-white p-3 shadow-lg" aria-label="{{ __('ui.nav.menu') }}">
                    <a class="staff-nav-link" href="{{ route('home') }}#doctors">{{ __('booking.choose_doctor') }}</a>
                    <a class="staff-nav-link" href="{{ route('home') }}#your-visit">{{ __('ui.nav.visit') }}</a>
                    <a class="staff-nav-link" href="{{ route('home') }}#visiting-information">{{ __('ui.nav.information') }}</a>
                    <a class="staff-nav-link" href="{{ route('home') }}#contact">{{ __('ui.nav.contact') }}</a>
                    <a class="staff-nav-link" href="{{ auth()->check() ? route('staff.home') : route('staff.login') }}">{{ auth()->check() ? __('staff.nav.workspace') : __('staff.nav.login') }}</a>
                </nav>
            </details>
        </div>
    </header>
    <main id="main-content" tabindex="-1">{{ $slot }}</main>
    <footer id="contact" class="border-t border-line bg-white">
        <div class="page-container grid gap-10 py-14 md:grid-cols-[1fr_1.2fr] md:gap-20">
            <div><x-brand /><p class="mt-5 text-sm text-muted">{{ __('ui.public.footer_note') }}</p></div>
            <div><h2 class="text-lg font-semibold tracking-tight">{{ __('ui.public.contact_title') }}</h2><p class="mt-3 max-w-lg text-sm leading-6 text-muted">{{ __('ui.public.contact_description') }}</p>
                @if($hospital['phone'])<a class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-care-700" href="tel:{{ preg_replace('/[^0-9+]/', '', $hospital['phone']) }}"><x-icon name="phone" class="size-4" />{{ $hospital['phone'] }}</a>@else<p class="mt-4 text-sm font-medium text-care-700">{{ __('ui.public.contact_fallback') }}</p>@endif
                @if($hospital['address'])<p class="mt-3 flex items-start gap-2 text-sm text-muted whitespace-pre-wrap"><x-icon name="pin" class="size-4" />{{ $hospital['address'] }}</p>@endif
            </div>
        </div>
        <div class="page-container flex flex-wrap items-center justify-between gap-3 border-t border-line py-6 text-[11px] text-muted"><p>© {{ now()->year }} {{ $hospital['name'] }}. {{ __('ui.public.footer_rights') }}</p><span>{{ __('ui.brand.department') }}</span></div>
    </footer>
</x-layouts.base>
