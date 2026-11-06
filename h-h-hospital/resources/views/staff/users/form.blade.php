<x-layouts.staff :title="$account->exists ? __('staff.users.edit') : __('staff.users.create')" :intro="__('staff.users.form_intro')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.admin.users.index')"><x-icon name="arrow-left" class="size-4" />{{ __('staff.users.back') }}</x-ui.button></x-slot:actions>
    <div class="grid items-start gap-6 xl:grid-cols-[1.5fr_1fr]">
        <form method="POST" action="{{ $account->exists ? route('staff.admin.users.update', $account) : route('staff.admin.users.store') }}" class="panel space-y-6 p-6 sm:p-8" data-loading-form novalidate>
            @csrf
            @if($account->exists) @method('PUT') @endif
            @if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ __('ui.library.errors') }}</x-ui.notification>@endif
            <x-form.input name="name" :label="__('staff.users.name')" :value="$account->name" :required="true" maxlength="100" autocomplete="off" />
            <x-form.input name="email" type="email" :label="__('staff.users.email')" :value="$account->email" :required="true" maxlength="255" autocomplete="off" />
            <div class="grid gap-5 sm:grid-cols-2"><x-form.select name="role" :label="__('staff.users.role')" :options="\App\Enums\StaffRole::options()" :value="$account->role->value" :required="true" /><x-form.select name="is_active" :label="__('staff.users.status')" :options="['1' => __('staff.status.active'), '0' => __('staff.status.inactive')]" :value="(int) $account->is_active" :required="true" /></div>
            <div class="border-t border-line pt-6"><x-form.input name="password" type="password" :label="$account->exists ? __('staff.users.new_password') : __('staff.users.password')" :hint="__('staff.users.password_hint')" :required="!$account->exists" maxlength="72" autocomplete="new-password" /></div>
            <x-form.input name="password_confirmation" type="password" :label="__('staff.users.password_confirmation')" :hint="$account->exists ? __('staff.users.password_optional') : null" :required="!$account->exists" maxlength="72" autocomplete="new-password" />
            <div class="flex flex-wrap justify-end gap-3 border-t border-line pt-6"><x-ui.button variant="secondary" :href="route('staff.admin.users.index')">{{ __('ui.actions.cancel') }}</x-ui.button><x-ui.button type="submit">{{ $account->exists ? __('staff.users.save') : __('staff.users.create') }}<x-icon name="check" class="size-4" /></x-ui.button></div>
        </form>
        <x-ui.notification :dismissible="false" :title="__('staff.home.access_title')">{{ __('staff.users.security_notice') }}</x-ui.notification>
    </div>
</x-layouts.staff>
