<x-layouts.base :title="__('staff.nav.login')">
    <div class="grid min-h-dvh lg:grid-cols-[1fr_1.05fr]">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-care-900 p-12 text-white lg:flex xl:p-16">
            <x-brand :light="true" />
            <div class="relative z-10 my-16 max-w-md" data-motion-enter><p class="eyebrow mb-7 text-care-200">{{ __('staff.auth.secure') }}</p><h2 class="font-display text-5xl leading-[1.15] tracking-tight xl:text-6xl">{{ str_replace('\\n', ' ', __('staff.auth.side_title')) }}</h2><p class="mt-7 max-w-sm text-base leading-7 text-care-100/80">{{ __('staff.auth.side_description') }}</p></div>
            <div class="relative z-10 flex flex-wrap gap-2">@foreach(\App\Enums\StaffRole::cases() as $role)<span class="rounded-full border border-white/15 px-3 py-1.5 text-xs text-care-100">{{ $role->label() }}</span>@endforeach</div>
            <div class="pointer-events-none absolute -right-40 -bottom-28 size-[36rem] rounded-full border-[70px] border-white/5" aria-hidden="true"></div>
        </aside>
        <main id="main-content" tabindex="-1" class="flex min-w-0 flex-col px-6 py-8 sm:px-12 lg:px-16">
            <div class="flex flex-wrap items-center justify-between gap-5"><span class="lg:hidden"><x-brand /></span><a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-medium text-muted hover:text-care-700"><x-icon name="arrow-left" class="size-4" />{{ __('staff.auth.back') }}</a></div>
            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-14 sm:py-20" data-motion-enter>
                <p class="eyebrow mb-5">{{ __('staff.auth.eyebrow') }}</p>
                <h1 class="font-display text-4xl tracking-tight text-ink sm:text-5xl">{{ __('staff.auth.title') }}</h1>
                <p class="mt-4 text-sm leading-6 text-muted">{{ __('staff.auth.intro') }}</p>
                @if(session('status'))<x-ui.notification class="mt-6" :dismissible="false">{{ session('status') }}</x-ui.notification>@endif
                @if($errors->any())<x-ui.notification variant="error" class="mt-6" :title="__('staff.auth.error_title')" :dismissible="false">{{ $errors->first() }}</x-ui.notification>@endif
                <form method="POST" action="{{ route('staff.login.store') }}" class="mt-9 space-y-6" data-loading-form>
                    @csrf
                    <x-form.input name="email" type="email" :label="__('staff.auth.email')" autocomplete="username" :required="true" maxlength="255" autofocus />
                    <x-form.input name="password" type="password" :label="__('staff.auth.password')" autocomplete="current-password" :required="true" maxlength="72" />
                    <x-ui.button type="submit" class="w-full">{{ __('staff.auth.submit') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button>
                </form>
                <p class="mt-7 border-t border-line pt-6 text-xs leading-6 text-muted">{{ __('staff.auth.help') }}</p>
            </div>
        </main>
    </div>
</x-layouts.base>
