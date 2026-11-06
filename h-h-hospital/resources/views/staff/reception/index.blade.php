<x-layouts.staff :title="__('reception.title')" :intro="__('reception.intro')">
    <x-slot:actions>@can('create', \App\Models\Appointment::class)<x-ui.button :href="route('staff.reception.walk-in')">{{ __('reception.walk_in') }}</x-ui.button>@endcan</x-slot:actions>
    <section class="panel overflow-hidden">
        <form method="GET" action="{{ route('staff.appointments.index') }}" class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
            <x-form.input name="q" :label="__('reception.search')" :value="$filters['q'] ?? ''" :hint="__('reception.search_hint')" maxlength="100" />
            <x-form.select name="doctor_id" :label="__('reception.doctor')" :value="$filters['doctor_id'] ?? ''" :options="$doctors->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->user->name])" :placeholder="__('reception.all_doctors')" />
            <x-form.input name="date" type="date" :label="__('reception.date')" :value="$filters['date']" />
            <div class="space-y-3"><input type="hidden" name="status" value="{{ $filters['status'] }}"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="older_pending" value="1" @checked($filters['older_pending'] ?? false)>{{ __('reception.older') }}</label><x-ui.button type="submit" variant="secondary">{{ __('reception.apply') }}</x-ui.button></div>
        </form>
        <nav class="flex flex-wrap gap-2 border-y border-line px-6 py-4" aria-label="{{ __('reception.status') }}">
            @foreach(\App\Enums\AppointmentStatus::cases() as $status)<a href="{{ route('staff.appointments.index', array_merge($filters, ['status' => $status->value, 'older_pending' => 0])) }}" @class(['rounded-lg px-4 py-2 text-xs font-semibold', 'bg-care-700 text-white' => $filters['status'] === $status->value && empty($filters['older_pending']), 'bg-canvas text-muted' => $filters['status'] !== $status->value || !empty($filters['older_pending'])]) @if($filters['status'] === $status->value && empty($filters['older_pending'])) aria-current="page" @endif>{{ __('ui.status.'.$status->value) }}</a>@endforeach
        </nav>
        @if(!empty($filters['older_pending']))<p class="p-6 text-sm text-muted">{{ __('reception.older_hint') }}</p>@endif
        @include('staff.reception.visits', ['visits' => $appointments])
        {{ $appointments->links() }}
    </section>
</x-layouts.staff>
