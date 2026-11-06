<x-layouts.staff :title="__('queue.title')" :intro="__('queue.intro')">
    <div data-doctor-queue data-refresh-url="{{ route('staff.doctor.queue.refresh') }}" data-date="{{ $state['date'] }}" data-current-id="{{ $state['current']?->id }}" data-loading-text="{{ __('queue.loading') }}" data-updated-text="{{ __('queue.updated') }}" data-error-text="{{ __('queue.refresh_failed') }}" data-session-text="{{ __('queue.session_ended') }}" data-changed-text="{{ __('queue.changed') }}" data-date-text="{{ __('queue.date_changed') }}" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4"><p class="max-w-2xl text-xs leading-6 text-muted">{{ __('queue.live_hint') }}</p><div class="flex flex-wrap gap-3"><x-ui.button type="button" variant="secondary" data-queue-refresh>{{ __('queue.refresh') }}</x-ui.button><a href="{{ route('staff.doctor.home') }}" data-queue-reload hidden class="button button-secondary">{{ __('queue.reload') }}</a></div></div>
        <p role="status" aria-live="polite" data-queue-feedback class="text-xs leading-6 text-muted"></p>
        @if($errors->has('queue'))<x-ui.notification variant="warning" :dismissible="false">{{ $errors->first('queue') }}</x-ui.notification>@endif
        <div data-queue-live class="space-y-6">@include('staff.queue.live')</div>
    </div>
</x-layouts.staff>
