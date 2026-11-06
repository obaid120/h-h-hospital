<x-layouts.staff :title="__('doctors.title')" :intro="__('doctors.intro')">
    <x-slot:actions>
        @can('create', \App\Models\Doctor::class)
            <x-ui.button :href="route('staff.admin.doctors.create')"><x-icon name="plus" class="size-4" />{{ __('doctors.create') }}</x-ui.button>
        @endcan
    </x-slot:actions>
    <form method="GET" action="{{ route('staff.doctors.index') }}" class="panel grid items-end gap-4 p-5 sm:grid-cols-[1fr_1fr_auto]">
        <x-form.input name="q" type="search" :label="__('doctors.search')" :value="$filters['q'] ?? ''" maxlength="100" />
        <x-form.select name="status" :label="__('doctors.status')" :options="['active' => __('staff.status.active'), 'inactive' => __('staff.status.inactive')]" :placeholder="__('staff.users.all_statuses')" :value="$filters['status'] ?? ''" />
        <div class="flex items-center gap-3"><x-ui.button type="submit">{{ __('staff.users.filter') }}</x-ui.button><a class="text-xs text-care-700" href="{{ route('staff.doctors.index') }}">{{ __('staff.users.reset') }}</a></div>
    </form>
    <section class="panel overflow-hidden">
        @if($doctors->isEmpty())<x-ui.empty-state :title="__('doctors.empty')" :description="__('doctors.empty_hint')" icon="heart" />
        @else
            <x-ui.table :caption="__('doctors.title')" :headers="[__('doctors.profile'), __('doctors.fee'), __('doctors.status'), __('staff.users.actions')]">
                @foreach($doctors as $doctor)
                    <tr><td><div class="flex items-center gap-3"><x-doctor-photo :doctor="$doctor" class="size-12 text-sm" /><div><p class="text-sm font-semibold">{{ $doctor->user->name }}</p><p class="mt-1 text-xs text-muted">{{ $doctor->specialization }}</p></div></div></td>
                        <td class="whitespace-nowrap text-xs">{{ \App\Models\Doctor::feeDisplay($doctor->normal_fee_minor) }}<p class="mt-1 text-muted">+ {{ \App\Models\Doctor::feeDisplay($doctor->priority_surcharge_minor) }} Priority</p></td>
                        <td><x-ui.badge :status="$doctor->is_active ? 'active' : 'inactive'" :label="__('staff.status.'.($doctor->is_active ? 'active' : 'inactive'))" />@if(!$doctor->user->is_active)<p class="mt-2 max-w-52 text-xs text-muted">{{ __('doctors.account_inactive') }}</p>@endif</td>
                        <td><div class="flex flex-wrap gap-1"><x-ui.button variant="ghost" size="sm" :href="route('staff.doctors.show', $doctor)">{{ __('doctors.schedule') }}</x-ui.button>@can('update', $doctor)<x-ui.button variant="ghost" size="sm" :href="route('staff.admin.doctors.edit', $doctor)">{{ __('doctors.edit') }}</x-ui.button>@endcan</div></td>
                    </tr>
                @endforeach
            </x-ui.table>
            {{ $doctors->links() }}
        @endif
    </section>
</x-layouts.staff>
