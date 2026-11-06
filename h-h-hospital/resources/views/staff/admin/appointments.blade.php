<x-layouts.staff :title="request()->routeIs('staff.admin.home') ? __('admin.overview') : __('admin.appointments')" :intro="__('admin.intro')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.patients.index')">{{ __('admin.patients') }}</x-ui.button><x-ui.button variant="secondary" :href="route('staff.admin.users.index')">{{ __('admin.staff') }}</x-ui.button></x-slot:actions>
    <section class="panel p-6">
        <form method="GET" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-form.input name="date" type="date" :label="__('admin.date')" :value="$filters['date']" />
            <x-form.select name="doctor_id" :label="__('reception.doctor')" :value="$filters['doctor_id'] ?? ''" :options="$doctors->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->user->name])" :placeholder="__('reception.all_doctors')" />
            <x-form.select name="source" :label="__('admin.source')" :value="$filters['source'] ?? ''" :options="collect(\App\Enums\AppointmentSource::cases())->mapWithKeys(fn ($source) => [$source->value => __('patients.sources.'.$source->value)])" :placeholder="__('admin.all_sources')" />
            <x-form.select name="status" :label="__('reception.status')" :value="$filters['status'] ?? ''" :options="collect(\App\Enums\AppointmentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => __('ui.status.'.$status->value)])" :placeholder="__('admin.all_statuses')" />
            <div class="flex items-end"><x-ui.button type="submit" variant="secondary">{{ __('admin.apply') }}</x-ui.button></div>
        </form>
    </section>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">@foreach(\App\Enums\AppointmentStatus::cases() as $status)<div class="stat-card"><p class="text-xs text-muted">{{ __('ui.status.'.$status->value) }}</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $counts[$status->value] ?? 0 }}</p></div>@endforeach</div>
    <p class="text-xs leading-6 text-muted">{{ __('admin.counts_hint') }}</p>
    <div class="flex flex-wrap gap-3"><x-ui.button variant="secondary" :href="route('staff.admin.finances', ['date' => $filters['date']])">{{ __('admin.money') }}</x-ui.button><x-ui.button variant="secondary" :href="route('staff.admin.activity', ['date' => $filters['date']])">{{ __('admin.activity') }}</x-ui.button></div>
    <section class="panel overflow-hidden">@include('staff.reception.visits', ['visits' => $appointments]){{ $appointments->links() }}</section>
</x-layouts.staff>
