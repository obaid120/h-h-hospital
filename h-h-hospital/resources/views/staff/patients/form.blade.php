<x-layouts.staff :title="__('patients.register')" :intro="__('patients.form_intro')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.patients.index')">{{ __('patients.back') }}</x-ui.button></x-slot:actions>
    <section class="panel overflow-hidden"><div class="p-6 sm:p-7"><h2 class="panel-title">{{ __('patients.lookup_title') }}</h2><p class="mt-2 text-xs leading-6 text-muted">{{ __('patients.lookup_hint') }}</p>
        <form method="GET" action="{{ route('staff.patients.create') }}" class="mt-5 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end"><div class="min-w-0"><x-form.input name="phone" id="lookup-phone" :label="__('patients.phone')" :value="$phone" :hint="__('patients.phone_hint')" type="tel" maxlength="40" :required="true" /></div><x-ui.button type="submit" variant="secondary">{{ __('patients.lookup') }}</x-ui.button></form>
    </div>
        @if($phone)
            @if($matches->isEmpty())<p class="border-t border-line px-6 py-5 text-sm text-muted">{{ __('patients.no_matches') }}</p>
            @else
                <x-ui.table :caption="__('patients.matches')" :headers="[__('patients.name'), __('patients.mr'), __('patients.age'), __('staff.users.actions')]">
                    @foreach($matches as $match)<tr><td class="text-sm font-semibold">{{ $match->name }}</td><td class="whitespace-nowrap text-xs tabular-nums">{{ $match->mr_number }}</td><td class="text-xs tabular-nums">{{ $match->age_years }}</td><td><x-ui.button size="sm" variant="secondary" :href="route('staff.patients.show', $match)">{{ __('patients.view') }}</x-ui.button></td></tr>@endforeach
                </x-ui.table>
                {{ $matches->links() }}
            @endif
        @endif
    </section>
    <form method="POST" action="{{ route('staff.patients.store') }}" class="panel max-w-3xl space-y-6 p-6 sm:p-8" data-loading-form novalidate>
        @csrf
        <input type="hidden" name="registration_key" value="{{ old('registration_key', $registrationKey) }}">
        <div><h2 class="panel-title">{{ __('patients.new_record') }}</h2><p class="mt-2 text-xs leading-6 text-muted">{{ __('patients.new_record_hint') }}</p></div>
        @if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ $errors->first('registration_key') ?: __('ui.library.errors') }}</x-ui.notification>@endif
        <x-form.input name="name" :label="__('patients.name')" maxlength="100" autocomplete="off" :required="true" />
        <div class="grid gap-5 sm:grid-cols-2"><x-form.input name="phone" id="registration-phone" type="tel" :label="__('patients.phone')" :value="$phone" :hint="__('patients.phone_hint')" maxlength="40" autocomplete="off" :required="true" /><x-form.input name="age_years" type="number" :label="__('patients.age')" :hint="__('patients.age_hint')" min="0" max="130" step="1" :required="true" /></div>
        <div class="space-y-2"><label class="flex items-start gap-3 text-sm leading-6" for="identity-confirmed"><input id="identity-confirmed" type="checkbox" name="identity_confirmed" value="1" class="mt-1 size-4 shrink-0 accent-care-700" @checked(old('identity_confirmed')) aria-describedby="identity-confirmed-error">{{ __('patients.confirmed') }}</label><x-form.error id="identity-confirmed-error" :message="$errors->first('identity_confirmed')" /></div>
        <div class="flex flex-wrap justify-between gap-3 border-t border-line pt-6"><x-ui.button variant="secondary" :href="route('staff.patients.create')">{{ __('staff.users.reset') }}</x-ui.button><x-ui.button type="submit">{{ __('patients.register') }}<x-icon name="check" class="size-4" /></x-ui.button></div>
    </form>
</x-layouts.staff>
