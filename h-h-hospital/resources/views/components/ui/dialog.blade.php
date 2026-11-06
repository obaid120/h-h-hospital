@props(['id', 'title', 'description' => null, 'drawer' => false])
<dialog {{ $attributes->class(['ui-dialog', 'navigation-drawer' => $drawer])->merge(['id' => $id, 'aria-labelledby' => $id.'-title', 'aria-describedby' => $description ? $id.'-description' : null]) }} data-dialog>
    <div data-dialog-panel>
        <div class="flex items-start justify-between gap-5 border-b border-line px-6 py-5">
            <div><h2 id="{{ $id }}-title" class="text-lg font-semibold tracking-tight text-ink">{{ $title }}</h2>@if($description)<p id="{{ $id }}-description" class="mt-2 text-sm leading-6 text-muted">{{ $description }}</p>@endif</div>
            <button type="button" class="icon-button -mr-2 shrink-0" data-close-dialog aria-label="{{ __('ui.actions.close') }}"><x-icon name="close" /></button>
        </div>
        <div class="p-6">{{ $slot }}</div>
        @isset($footer)<div class="flex flex-wrap justify-end gap-3 border-t border-line bg-canvas px-6 py-4">{{ $footer }}</div>@endisset
    </div>
</dialog>
