@props(['variant' => 'info', 'title' => null, 'dismissible' => true])
@php($tone = in_array($variant, ['info', 'success', 'warning', 'error']) ? $variant : 'info')
<div {{ $attributes->class(['notification', 'notification-'.$tone])->merge(['role' => $tone === 'error' ? 'alert' : 'status']) }} data-notification>
    <x-icon :name="$tone === 'success' ? 'check' : 'info'" class="mt-0.5" />
    <div class="min-w-0 flex-1">@if($title)<p class="font-semibold">{{ $title }}</p>@endif<div class="notification-copy">{{ $slot }}</div></div>
    @if($dismissible)<button type="button" class="icon-button -m-1" data-dismiss-notification aria-label="{{ __('ui.actions.dismiss') }}"><x-icon name="close" class="size-4" /></button>@endif
</div>
