<x-layouts.staff :title="$doctor->exists ? __('doctors.edit') : __('doctors.create')" :intro="__('doctors.intro')">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.doctors.index')">{{ __('doctors.back') }}</x-ui.button>@if($doctor->exists)<x-ui.button :href="route('staff.doctors.show', $doctor)">{{ __('doctors.schedule') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button>@endif</x-slot:actions>
    <form method="POST" enctype="multipart/form-data" action="{{ $doctor->exists ? route('staff.admin.doctors.update', $doctor) : route('staff.admin.doctors.store') }}" class="panel mx-auto max-w-4xl space-y-7 p-6 sm:p-8" data-loading-form novalidate>
        @csrf @if($doctor->exists) @method('PUT') @endif
        @if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ __('ui.library.errors') }}</x-ui.notification>@endif
        @if(!$doctor->exists)
            <fieldset class="space-y-5"><legend class="panel-title mb-5">{{ __('doctors.account') }}</legend>
                <x-form.select name="account_mode" :label="__('doctors.account_mode')" :options="['new' => __('doctors.new_account'), 'existing' => __('doctors.existing_account')]" value="new" :hint="__('doctors.account_hint')" :required="true" />
                <details @if(old('account_mode', 'new') === 'new') open @endif class="rounded-xl border border-line p-5"><summary class="text-sm font-semibold">{{ __('doctors.new_account') }}</summary><div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" :label="__('staff.users.name')" maxlength="100" autocomplete="off" />
                    <x-form.input name="email" type="email" :label="__('staff.users.email')" maxlength="255" autocomplete="off" />
                    <x-form.input name="password" type="password" :label="__('staff.users.password')" :hint="__('staff.users.password_hint')" maxlength="72" autocomplete="new-password" />
                    <x-form.input name="password_confirmation" type="password" :label="__('staff.users.password_confirmation')" maxlength="72" autocomplete="new-password" />
                </div></details>
                <details @if(old('account_mode') === 'existing') open @endif class="rounded-xl border border-line p-5"><summary class="text-sm font-semibold">{{ __('doctors.existing_account') }}</summary><div class="mt-5"><x-form.select name="user_id" :label="__('doctors.select_account')" :options="$accounts" :placeholder="__('doctors.select_account')" /></div></details>
            </fieldset>
        @else
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-canvas p-5"><div><p class="font-semibold">{{ $doctor->user->name }}</p><p class="mt-1 text-xs text-muted">{{ $doctor->user->email }}</p></div><x-ui.button variant="secondary" size="sm" :href="route('staff.admin.users.edit', $doctor->user)">{{ __('doctors.manage_account') }}</x-ui.button></div>
        @endif
        <div class="grid gap-5 sm:grid-cols-2"><x-form.input name="specialization" :label="__('doctors.specialization')" :value="$doctor->specialization" :required="true" maxlength="120" /><x-form.select name="is_active" :label="__('doctors.status')" :options="['1' => __('staff.status.active'), '0' => __('staff.status.inactive')]" :value="(int) $doctor->is_active" :hint="__('doctors.status_hint')" :required="true" /></div>
        <x-form.textarea name="biography" :label="__('doctors.biography')" :value="$doctor->biography" maxlength="5000" rows="4" />
        <div class="grid gap-5 sm:grid-cols-2"><x-form.input name="normal_fee" :label="__('doctors.fee')" :value="\App\Models\Doctor::feeInput($doctor->normal_fee_minor)" :hint="__('doctors.fee_hint', ['currency' => config('hospital.currency')])" inputmode="decimal" :required="true" /><x-form.input name="priority_surcharge" :label="__('doctors.surcharge')" :value="\App\Models\Doctor::feeInput($doctor->priority_surcharge_minor)" :hint="__('doctors.priority_hint')" inputmode="decimal" :required="true" /></div>
        <div class="space-y-5 border-t border-line pt-6">
            @if($doctor->exists)<x-doctor-photo :doctor="$doctor" class="size-24 text-2xl" />@endif
            <x-form.input name="photo" type="file" :label="__('doctors.photo')" :hint="__('doctors.photo_hint')" accept="image/jpeg,image/png,image/webp" />
            @if($doctor->photo_path)<label class="flex items-center gap-3 text-sm"><input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>{{ __('doctors.remove_photo') }}</label>@endif
        </div>
        <div class="flex justify-end border-t border-line pt-6"><x-ui.button type="submit">{{ $doctor->exists ? __('doctors.save') : __('doctors.create') }}<x-icon name="check" class="size-4" /></x-ui.button></div>
    </form>
</x-layouts.staff>
