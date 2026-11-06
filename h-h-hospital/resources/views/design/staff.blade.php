<x-layouts.staff :title="__('ui.staff.title')" :eyebrow="__('ui.staff.eyebrow')" :intro="__('ui.staff.intro')" :preview="true">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('design.components')"><x-icon name="layers" class="size-4" />{{ __('ui.nav.components') }}</x-ui.button></x-slot:actions>
    <x-ui.notification :dismissible="false">{{ __('ui.staff.preview_message') }}</x-ui.notification>
    <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-4">
        @foreach([['label' => 'visits', 'value' => '06', 'icon' => 'calendar'], ['label' => 'waiting', 'value' => '02', 'icon' => 'clock'], ['label' => 'checked_in', 'value' => '02', 'icon' => 'users'], ['label' => 'finished', 'value' => '01', 'icon' => 'check']] as $stat)
            <div class="stat-card"><div class="mb-4 flex items-center justify-between gap-2"><span class="flex size-8 items-center justify-center rounded-lg bg-care-50 text-care-700"><x-icon :name="$stat['icon']" class="size-4" /></span><span class="text-[9px] tracking-wide text-muted">{{ __('ui.staff.preview') }}</span></div><p class="text-3xl font-semibold tracking-[-.04em] tabular-nums">{{ $stat['value'] }}</p><p class="mt-2 text-[11px] leading-5 text-muted">{{ __('ui.staff.'.$stat['label']) }}</p></div>
        @endforeach
    </div>
    @include('design.partials.visit-table')
    <div class="grid gap-6 xl:grid-cols-[1fr_1.1fr]">
        <div class="rounded-panel bg-care-900 p-7 text-white"><div class="mb-6 flex size-10 items-center justify-center rounded-xl bg-white/10"><x-icon name="layers" /></div><h2 class="font-display text-3xl leading-tight">{{ __('ui.staff.quick_title') }}</h2><p class="mt-4 max-w-sm text-sm leading-6 text-care-100/85">{{ __('ui.staff.quick_description') }}</p><a href="{{ route('design.components') }}" class="mt-7 inline-flex items-center gap-3 text-xs font-semibold text-white">{{ __('ui.staff.explore_components') }}<x-icon name="arrow-right" class="size-4" /></a></div>
        <div class="panel"><x-ui.empty-state :title="__('ui.staff.empty_title')" :description="__('ui.staff.empty_description')" /></div>
    </div>
</x-layouts.staff>
