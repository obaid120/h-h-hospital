<x-layouts.staff :title="__('staff.users.title')" :intro="__('staff.users.intro')">
    <x-slot:actions><x-ui.button :href="route('staff.admin.users.create')"><x-icon name="plus" class="size-4" />{{ __('staff.users.create') }}</x-ui.button></x-slot:actions>
    <form method="GET" action="{{ route('staff.admin.users.index') }}" class="panel grid items-end gap-4 p-5 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
        <x-form.input name="q" type="search" :label="__('staff.users.search')" :placeholder="__('staff.users.search_placeholder')" :value="$filters['q'] ?? ''" maxlength="100" />
        <x-form.select name="role" :label="__('staff.users.role')" :options="\App\Enums\StaffRole::options()" :placeholder="__('staff.users.all_roles')" :value="$filters['role'] ?? ''" />
        <x-form.select name="status" :label="__('staff.users.status')" :options="['active' => __('staff.status.active'), 'inactive' => __('staff.status.inactive')]" :placeholder="__('staff.users.all_statuses')" :value="$filters['status'] ?? ''" />
        <div class="flex items-center gap-2"><x-ui.button type="submit">{{ __('staff.users.filter') }}</x-ui.button>@if(array_filter($filters))<a class="text-xs font-medium text-care-700" href="{{ route('staff.admin.users.index') }}">{{ __('staff.users.reset') }}</a>@endif</div>
    </form>
    <section class="panel overflow-hidden">
        @if($users->isEmpty())<x-ui.empty-state :title="__('staff.users.empty_title')" :description="__('staff.users.empty_description')" icon="users" />
        @else
            <x-ui.table :caption="__('staff.users.title')" :headers="[__('staff.users.name'), __('staff.users.role'), __('staff.users.status'), __('staff.users.actions')]">
                @foreach($users as $account)<tr><td><div class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-care-50 text-[10px] font-semibold text-care-700">{{ $account->initials() }}</span><div><p class="whitespace-nowrap text-xs font-semibold">{{ $account->name }} @if($account->is(auth()->user()))<span class="ml-1 text-[10px] font-normal text-muted">({{ __('staff.users.you') }})</span>@endif</p><p class="mt-1 text-xs text-muted">{{ $account->email }}</p></div></div></td><td class="whitespace-nowrap text-xs">{{ $account->role->label() }}</td><td><x-ui.badge :status="$account->is_active ? 'active' : 'inactive'" :label="__('staff.status.'.($account->is_active ? 'active' : 'inactive'))" /></td><td><x-ui.button variant="ghost" size="sm" :href="route('staff.admin.users.edit', $account)" :aria-label="__('staff.users.edit_for', ['name' => $account->name])">{{ __('staff.users.edit') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button></td></tr>@endforeach
            </x-ui.table>
            {{ $users->links() }}
        @endif
    </section>
</x-layouts.staff>
