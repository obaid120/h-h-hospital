@props(['title', 'eyebrow' => null, 'intro' => null, 'preview' => false])
@php
    $identity = $preview ? null : auth()->user();
@endphp
<x-layouts.base :title="$title" :data-staff-session="$preview ? null : 'true'">
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-line bg-white lg:flex" data-screen-only>
        <div class="flex h-24 shrink-0 items-center border-b border-line px-6"><x-brand /></div>
        <x-staff-navigation :preview="$preview" class="min-h-0 flex-1 overflow-y-auto px-4 py-7" />
        <div class="m-4 shrink-0 rounded-xl border border-care-100 bg-care-50 px-4 py-4"><p class="truncate text-xs font-semibold text-care-800">{{ $identity?->name ?? __('ui.staff.team') }}</p><p class="mt-1 text-[11px] text-muted">{{ $identity?->role->label() ?? __('ui.brand.department') }}</p></div>
    </aside>
    <div class="staff-content min-h-dvh lg:pl-64">
        <header class="sticky top-0 z-20 flex h-20 items-center justify-between gap-4 border-b border-line bg-canvas/95 px-5 backdrop-blur sm:px-8" data-screen-only>
            <div class="flex min-w-0 flex-1 items-center gap-3"><button type="button" class="icon-button -ml-2 shrink-0 lg:hidden" data-open-dialog="staff-navigation" aria-haspopup="dialog" aria-controls="staff-navigation" aria-label="{{ __('ui.nav.menu') }}"><x-icon name="menu" /></button><span class="truncate text-xs text-muted"><span class="hidden sm:inline">{{ __('ui.nav.workspace') }}<span class="mx-3 text-line">/</span></span><span class="font-medium text-ink">{{ $title }}</span></span></div>
            <div class="flex shrink-0 items-center gap-3"><span class="hidden rounded-full border border-care-100 bg-care-50 px-3 py-1 text-[10px] font-medium text-care-800 sm:inline-flex">{{ $preview ? __('ui.staff.preview') : $identity->role->label() }}</span>@if($preview)<span class="flex size-9 items-center justify-center rounded-full border border-line bg-white text-[11px] font-semibold">HH</span>@else<a href="{{ route('staff.profile', $identity) }}" class="flex size-9 items-center justify-center rounded-full border border-line bg-white text-[11px] font-semibold" aria-label="{{ __('staff.nav.account') }}">{{ $identity->initials() }}</a>@endif</div>
        </header>
        <main id="main-content" tabindex="-1" class="mx-auto max-w-[1440px] space-y-7 px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
            <div class="flex flex-wrap items-end justify-between gap-5" data-motion-enter><div class="min-w-0 max-w-full">@if($eyebrow)<p class="eyebrow mb-3">{{ $eyebrow }}</p>@endif<h1 class="text-[28px] leading-tight font-semibold tracking-[-.035em] sm:text-3xl">{{ $title }}</h1>@if($intro)<p class="mt-3 text-sm leading-6 text-muted">{{ $intro }}</p>@endif</div>{{ $actions ?? '' }}</div>
            @if(session('status'))<x-ui.notification variant="success">{{ session('status') }}</x-ui.notification>@endif
            {{ $slot }}
        </main>
    </div>
    <x-ui.dialog id="staff-navigation" :title="app(\App\Services\HospitalSettings::class)->current()['name']" :drawer="true"><x-staff-navigation :preview="$preview" /></x-ui.dialog>
</x-layouts.base>
