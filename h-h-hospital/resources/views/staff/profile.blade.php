<x-layouts.staff :title="auth()->id() === $staffUser->id ? __('staff.users.own_profile') : __('staff.users.profile')" :intro="__('staff.users.profile_intro')">
    <x-slot:actions>
        @can('update', $staffUser)
            <x-ui.button variant="secondary" :href="route('staff.admin.users.edit', $staffUser)">{{ __('staff.users.edit') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button>
        @endcan
    </x-slot:actions>
    <section class="panel max-w-2xl p-6 sm:p-8">
        <div class="mb-8 flex items-center gap-4"><span class="flex size-14 items-center justify-center rounded-2xl bg-care-50 text-lg font-semibold text-care-700">{{ $staffUser->initials() }}</span><div><h2 class="text-xl font-semibold tracking-tight">{{ $staffUser->name }}</h2><p class="mt-1 text-xs text-muted">{{ $staffUser->role->label() }}</p></div></div>
        <dl class="space-y-5 text-sm"><div class="flex flex-wrap justify-between gap-2 border-b border-line pb-5"><dt class="text-muted">{{ __('staff.users.email') }}</dt><dd class="break-all font-medium">{{ $staffUser->email }}</dd></div><div class="flex justify-between gap-3 border-b border-line pb-5"><dt class="text-muted">{{ __('staff.users.status') }}</dt><dd><x-ui.badge :status="$staffUser->is_active ? 'active' : 'inactive'" :label="__('staff.status.'.($staffUser->is_active ? 'active' : 'inactive'))" /></dd></div><div class="flex justify-between gap-3"><dt class="text-muted">{{ __('staff.users.created_at') }}</dt><dd class="font-medium">{{ $staffUser->created_at->timezone(config('hospital.timezone'))->format('d M Y') }}</dd></div></dl>
        @cannot('update', $staffUser)
            <p class="mt-8 rounded-xl bg-canvas p-4 text-xs leading-6 text-muted">{{ __('staff.users.profile_help') }}</p>
        @endcannot
    </section>
</x-layouts.staff>
