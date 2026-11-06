@props(['title', 'description' => null, 'icon' => 'calendar'])
<div {{ $attributes->class(['empty-state']) }}>
    <span class="empty-state-icon"><x-icon :name="$icon" class="size-6" /></span>
    <h3 class="mt-5 text-base font-semibold text-ink">{{ $title }}</h3>
    @if($description)<p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-muted">{{ $description }}</p>@endif
    @if($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
