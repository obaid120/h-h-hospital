<x-layouts.public :description="__('ui.public.intro')">
    <section class="page-container grid items-center gap-12 py-16 sm:py-20 lg:grid-cols-[1.05fr_1fr] lg:gap-16 lg:py-24">
        <div data-motion-enter>
            <p class="eyebrow mb-7 flex items-center gap-3"><span class="size-1.5 rounded-full bg-care-700" aria-hidden="true"></span>{{ __('ui.public.eyebrow') }}</p>
            <h1 class="max-w-xl font-display text-[clamp(2.75rem,5vw,4.5rem)] leading-[1.08] tracking-[-.04em]">{{ __('ui.public.heading') }}<span class="mt-1 block text-care-700 italic">{{ __('ui.public.heading_accent') }}</span></h1>
            <p class="mt-7 max-w-md text-base leading-7 text-muted">{{ __('ui.public.intro') }}</p>
            <div class="mt-9 flex flex-wrap gap-3"><x-ui.button href="#doctors">{{ __('booking.choose_doctor') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button><x-ui.button variant="ghost" href="#contact">{{ __('ui.public.reception') }}</x-ui.button></div>
            <p class="mt-7 flex max-w-sm items-start gap-2.5 text-xs leading-5 text-muted"><x-icon name="info" class="mt-0.5 size-3.5" />{{ __('ui.public.availability') }}</p>
        </div>
        <div class="care-visual" data-motion-enter>
            <div class="care-orbit" aria-hidden="true"></div>
            <div class="journey-card">
                <div class="mb-7 flex items-center justify-between"><p class="eyebrow">{{ __('ui.public.journey_eyebrow') }}</p><span class="flex size-8 items-center justify-center rounded-lg bg-care-50 text-care-700"><x-icon name="heart" class="size-4" /></span></div>
                <h2 class="max-w-56 font-display text-[29px] leading-tight tracking-tight">{{ __('ui.public.journey_title') }}</h2>
                <ol class="my-7 space-y-5">@foreach(__('ui.public.steps') as $step)<li class="flex items-center gap-3.5"><span class="step-number">0{{ $loop->iteration }}</span><span class="text-sm font-medium">{{ $step['title'] }}</span>@if($loop->last)<x-icon name="check" class="ml-auto size-4 text-care-700" />@endif</li>@endforeach</ol>
                <p class="border-t border-line pt-5 text-[11px] leading-5 text-muted">{{ __('ui.public.journey_note') }}</p>
            </div>
        </div>
    </section>
        <section id="doctors" class="page-container pb-16 sm:pb-20"><div class="mb-8 max-w-xl"><p class="eyebrow mb-4">{{ __('doctors.public_title') }}</p><h2 class="section-heading">{{ __('doctors.public_title') }}</h2><p class="mt-4 text-sm leading-6 text-muted">{{ __('doctors.public_intro') }}</p></div>
            @if($doctors->isEmpty())
                <x-ui.empty-state :title="__('booking.no_doctors')" :description="__('booking.no_doctors_hint')" icon="heart" />
            @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach($doctors as $doctor)
                <article class="panel flex flex-col p-6" data-motion-enter data-doctor-card><div class="flex items-center gap-4"><x-doctor-photo :doctor="$doctor" class="size-20 text-xl" /><div class="min-w-0"><h3 class="text-base font-semibold break-words">{{ $doctor->user->name }}</h3><p class="mt-1 text-xs text-care-700 break-words">{{ $doctor->specialization }}</p></div></div>
                    @if($doctor->biography)<p class="mt-5 text-sm leading-6 text-muted break-words">{{ \Illuminate\Support\Str::limit($doctor->biography, 220) }}</p>@endif
                    <p class="mt-5 text-sm font-semibold">{{ \App\Models\Doctor::feeDisplay($doctor->normal_fee_minor) }}</p>
                    <ul class="mt-4 mb-6 space-y-2 text-xs text-muted">@forelse($doctor->weeklySchedules as $hours)<li class="flex justify-between gap-3"><span>{{ __('doctors.weekdays.'.$hours->weekday) }}</span><span class="whitespace-nowrap">{{ substr($hours->opens_at, 0, 5) }} – {{ substr($hours->closes_at, 0, 5) }}</span></li>@empty<li>{{ __('doctors.no_hours') }}</li>@endforelse</ul>
                    <x-ui.button :href="route('booking.create', $doctor)" class="mt-auto" data-open-dialog="online-booking" data-booking-url="{{ route('booking.create', $doctor) }}" aria-haspopup="dialog" aria-controls="online-booking" :aria-label="__('booking.book').' — '.$doctor->user->name">{{ __('booking.book') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button>
                </article>
            @endforeach</div>
            <div class="mt-5">{{ $doctors->links() }}</div>
            @endif
        </section>
    <x-ui.dialog id="online-booking" :title="__('booking.title')" class="booking-dialog"><div data-booking-panel data-loading-label="{{ __('booking.loading') }}" data-error-label="{{ __('booking.form_error') }}" data-open-page-label="{{ __('booking.open_page') }}"><p class="text-sm text-muted" role="status">{{ __('booking.loading') }}</p></div></x-ui.dialog>
    <section id="your-visit" class="border-y border-line bg-white py-16 sm:py-20">
        <div class="page-container"><div class="mb-10 max-w-xl"><p class="eyebrow mb-4">{{ __('ui.public.steps_eyebrow') }}</p><h2 class="section-heading">{{ __('ui.public.steps_title') }}</h2></div>
            <div class="grid gap-10 md:grid-cols-3">@foreach(__('ui.public.steps') as $step)<article class="border-t border-line pt-7" data-motion-enter><div class="mb-5 flex items-center justify-between"><span class="flex size-11 items-center justify-center rounded-xl border border-care-100 bg-care-50 text-care-700"><x-icon :name="$step['icon']" /></span><span class="font-display text-3xl text-care-200">0{{ $loop->iteration }}</span></div><h3 class="text-base font-semibold">{{ $step['title'] }}</h3><p class="mt-3 max-w-xs text-sm leading-6 text-muted">{{ $step['description'] }}</p></article>@endforeach</div>
        </div>
    </section>
    <section id="visiting-information" class="page-container py-16 sm:py-20">
        <div class="grid gap-10 rounded-[1.5rem] bg-care-900 px-7 py-10 text-white sm:p-12 lg:grid-cols-2 lg:gap-20">
            <div><p class="eyebrow mb-5 text-care-200">{{ __('ui.public.prepare_eyebrow') }}</p><h2 class="font-display text-3xl leading-tight tracking-tight sm:text-4xl">{{ str_replace('\\n', ' ', __('ui.public.prepare_title')) }}</h2><p class="mt-5 max-w-sm text-sm leading-6 text-care-100/80">{{ __('ui.public.prepare_description') }}</p></div>
            <ul class="space-y-0 self-center">@foreach(__('ui.public.prepare_items') as $item)<li class="flex items-start gap-4 border-b border-white/10 py-5 first:pt-0 last:border-0 last:pb-0"><span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-white/10"><x-icon name="check" class="size-3" /></span><span class="text-sm leading-6 text-care-50">{{ $item }}</span></li>@endforeach</ul>
        </div>
    </section>
</x-layouts.public>
