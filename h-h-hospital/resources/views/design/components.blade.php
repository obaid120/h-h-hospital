<x-layouts.staff :title="__('ui.library.title')" :eyebrow="__('ui.library.eyebrow')" :intro="__('ui.library.intro')" :preview="true">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('design.staff')"><x-icon name="arrow-left" class="size-4" />{{ __('ui.actions.back') }}</x-ui.button></x-slot:actions>
    <div class="panel px-6 py-6"><h2 class="panel-title">{{ __('ui.library.tokens') }}</h2><p class="panel-description">{{ __('ui.library.tokens_description') }}</p><div class="mt-6 grid gap-6 sm:grid-cols-[1fr_1.4fr]">
        <div><p class="font-display text-3xl text-care-800">{{ __('ui.library.type_sample') }}</p><p class="mt-3 text-xs leading-5 text-muted">{{ __('ui.library.spacing') }}</p></div>
        <div class="grid grid-cols-4 gap-3">@foreach(['primary' => 'bg-care-700', 'ink' => 'bg-ink', 'surface' => 'bg-canvas', 'border' => 'bg-line'] as $color => $class)<div><span class="block h-12 rounded-xl border border-line {{ $class }}" aria-hidden="true"></span><p class="mt-2 text-[10px] font-medium text-muted">{{ __('ui.library.color_'.$color) }}</p></div>@endforeach</div>
    </div></div>
    <div class="grid items-start gap-6 xl:grid-cols-[1.1fr_1fr]">
        <section class="panel">
            <div class="panel-heading"><div><h2 class="panel-title">{{ __('ui.library.forms') }}</h2><p class="panel-description">{{ __('ui.library.forms_description') }}</p></div><x-icon name="users" class="text-care-700" /></div>
            <form action="{{ route('design.components.check') }}" method="POST" class="space-y-5 p-6" data-loading-form novalidate>
                @csrf
                @if($errors->any())<x-ui.notification variant="error" :dismissible="false">{{ __('ui.library.errors') }}</x-ui.notification>@endif
                <x-form.input name="patient_name" :label="__('ui.library.name')" :placeholder="__('ui.library.name_placeholder')" autocomplete="name" :required="true" maxlength="100" />
                <x-form.input name="phone" type="tel" :label="__('ui.library.phone')" :hint="__('ui.library.phone_hint')" placeholder="03XX XXXXXXX" autocomplete="tel" :required="true" maxlength="20" />
                <x-form.select name="department" :label="__('ui.library.department')" :placeholder="__('ui.library.choose_department')" :options="['general' => __('ui.library.general'), 'specialist' => __('ui.library.specialist')]" :required="true" />
                <x-form.textarea name="notes" :label="__('ui.library.notes')" :placeholder="__('ui.library.notes_placeholder')" maxlength="500" />
                <x-form.input name="preview_reference" :label="__('ui.library.disabled_label')" :hint="__('ui.library.disabled_hint')" value="—" :disabled="true" />
                <div class="flex justify-end border-t border-line pt-5"><x-ui.button type="submit">{{ __('ui.actions.check') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button></div>
            </form>
        </section>
        <div class="space-y-6">
            <section class="panel p-6"><h2 class="panel-title">{{ __('ui.library.states') }}</h2><p class="panel-description">{{ __('ui.library.states_description') }}</p><div class="mt-6 flex flex-wrap gap-3"><x-ui.button>{{ __('ui.library.primary') }}</x-ui.button><x-ui.button variant="secondary">{{ __('ui.library.secondary') }}</x-ui.button><x-ui.button :disabled="true">{{ __('ui.library.disabled') }}</x-ui.button><x-ui.button :loading="true">{{ __('ui.library.loading') }}</x-ui.button><x-ui.button variant="danger">{{ __('ui.library.danger') }}</x-ui.button></div><h3 class="mt-7 text-xs font-semibold">{{ __('ui.library.badges') }}</h3><div class="mt-3 flex flex-wrap gap-2">@foreach(['pending', 'confirmed', 'completed', 'cancelled', 'priority', 'full', 'closed'] as $status)<x-ui.badge :status="$status" />@endforeach</div></section>
            <x-ui.notification :title="__('ui.library.notice_title')">{{ __('ui.library.notice') }}</x-ui.notification>
            <section class="panel p-6"><h2 class="panel-title">{{ __('ui.library.dialog_title') }}</h2><p class="mt-3 text-sm leading-6 text-muted">{{ __('ui.library.dialog_description') }}</p><x-ui.button variant="secondary" class="mt-5" data-open-dialog="component-dialog" aria-controls="component-dialog" aria-haspopup="dialog">{{ __('ui.actions.open_dialog') }}<x-icon name="arrow-right" class="size-4" /></x-ui.button></section>
        </div>
    </div>
    @include('design.partials.visit-table')
    <section class="panel"><x-ui.empty-state :title="__('ui.library.empty_title')" :description="__('ui.library.empty_description')" /></section>
    <x-ui.dialog id="component-dialog" :title="__('ui.library.dialog_title')" :description="__('ui.library.dialog_description')">
        <p class="text-sm leading-7 text-muted">{{ __('ui.library.dialog_body') }}</p>
        <x-slot:footer><x-ui.button variant="secondary" data-close-dialog>{{ __('ui.actions.cancel') }}</x-ui.button><x-ui.button data-close-dialog>{{ __('ui.actions.close') }}</x-ui.button></x-slot:footer>
    </x-ui.dialog>
</x-layouts.staff>
