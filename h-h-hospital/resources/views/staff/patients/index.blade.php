<x-layouts.staff :title="__('patients.title')" :intro="__('patients.intro')">
    <x-slot:actions>
        @can('create', \App\Models\Patient::class)<x-ui.button :href="route('staff.patients.create')"><x-icon name="plus" class="size-4" />{{ __('patients.register') }}</x-ui.button>@endcan
    </x-slot:actions>
    <form method="GET" action="{{ route('staff.patients.index') }}" class="panel grid items-start gap-4 p-5 sm:grid-cols-[1fr_1.5fr_auto]">
        <x-form.select name="search_by" :label="__('patients.search_by')" :options="__('patients.search_options')" :value="$filters['search_by']" />
        <x-form.input name="q" type="search" :label="__('patients.search')" :value="$filters['q'] ?? ''" :hint="__('patients.search_hint')" maxlength="100" />
        <div class="flex items-center gap-3 sm:pt-6"><x-ui.button type="submit">{{ __('staff.users.filter') }}</x-ui.button><a class="text-xs text-care-700" href="{{ route('staff.patients.index') }}">{{ __('staff.users.reset') }}</a></div>
    </form>
    <section class="panel overflow-hidden">
        @if($patients->isEmpty())<x-ui.empty-state :title="__('patients.empty')" :description="__('patients.empty_hint')" icon="users" />
        @else
            <x-ui.table :caption="__('patients.title')" :headers="[__('patients.name'), __('patients.mr'), __('patients.phone'), __('patients.age'), __('staff.users.actions')]">
                @foreach($patients as $patient)<tr>
                    <td class="min-w-40 text-sm font-semibold">{{ $patient->name }}@if($patient->is_demo)<p class="mt-1 text-xs font-normal text-muted">{{ __('patients.demo') }}</p>@endif</td>
                    <td class="whitespace-nowrap text-xs font-semibold tabular-nums">{{ $patient->mr_number }}</td><td class="whitespace-nowrap text-xs tabular-nums">{{ $patient->phone }}</td><td class="text-xs tabular-nums">{{ $patient->age_years }}</td>
                    <td><x-ui.button variant="ghost" size="sm" :href="route('staff.patients.show', $patient)">{{ __('patients.view') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button></td>
                </tr>@endforeach
            </x-ui.table>
            {{ $patients->links() }}
        @endif
    </section>
</x-layouts.staff>
