<x-layouts.base title="403">
    <main id="main-content" tabindex="-1" class="mx-auto flex min-h-dvh max-w-lg flex-col justify-center px-6 py-12"><x-brand /><p class="eyebrow mt-14 mb-4">403</p><h1 class="font-display text-4xl leading-tight">{{ __('staff.forbidden.title') }}</h1><p class="mt-5 text-sm leading-7 text-muted">{{ __('staff.forbidden.description') }}</p><x-ui.button class="mt-8 self-start" :href="route('staff.home')">{{ __('staff.forbidden.back') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button></main>
</x-layouts.base>
