@if(!$state['doctor'])
    <x-ui.notification variant="warning" :dismissible="false">{{ __('queue.no_profile') }}</x-ui.notification>
@else
    <div class="flex flex-wrap items-center justify-between gap-4"><p class="text-sm font-semibold">{{ $state['doctor']->user->name }} · {{ $state['date'] }} <span class="text-xs font-normal text-muted">{{ config('hospital.timezone') }}</span></p><x-ui.badge status="confirmed" :label="__('queue.waiting').' · '.$state['waiting']->count()" /></div>
    <section class="panel space-y-5 p-6 sm:p-8" aria-label="{{ __('queue.current') }}"><h2 class="panel-title">{{ __('queue.current') }}</h2>
        @if($state['current'])@php($current = $state['current'])
            <div class="flex items-start gap-5 sm:items-center"><div class="flex size-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-care-700 text-white"><span class="text-[10px] uppercase">{{ __('queue.token') }}</span><strong class="text-3xl tabular-nums">{{ $current->token_number }}</strong></div><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">{{ $current->patient->name }}</h3><x-ui.badge :status="$current->is_priority ? 'priority' : 'confirmed'" :label="$current->is_priority ? __('queue.priority') : __('queue.normal')" /></div><p class="mt-2 text-sm text-muted">{{ $current->patient->mr_number }} · {{ __('queue.age') }}: {{ $current->verified_age }}</p><p class="mt-2 text-xs text-muted">{{ __('queue.started', ['time' => $current->consultation_started_at->setTimezone(config('hospital.timezone'))->format('H:i')]) }}</p></div></div>
            <p class="text-xs leading-6 text-muted">{{ __('queue.in_progress') }}</p><div class="flex flex-wrap gap-3"><x-ui.button :href="route('staff.prescriptions.edit', $current)">{{ __('prescription.edit') }}</x-ui.button><x-ui.button variant="secondary" :href="route('staff.patients.show', $current->patient)">{{ __('queue.history') }}</x-ui.button></div>
        @else<p class="text-sm text-muted">{{ __('queue.no_current') }}</p>@endif
        <form method="POST" action="{{ route('staff.doctor.queue.next') }}" data-loading-form>
            @csrf<input type="hidden" name="queue_date" value="{{ $state['date'] }}"><input type="hidden" name="queue_version" value="{{ $state['version'] }}">
            <x-ui.button type="submit" :disabled="(bool) $state['current'] || $state['waiting']->isEmpty()">{{ __('queue.next') }}</x-ui.button>
        </form>
    </section>
    <section class="panel overflow-hidden" aria-label="{{ __('queue.waiting') }}"><h2 class="panel-title p-6">{{ __('queue.waiting') }}</h2>
        @if($state['waiting']->isEmpty())<p class="px-6 pb-6 text-sm text-muted">{{ __('queue.empty') }}</p>@else
            <x-ui.table :caption="__('queue.waiting')" :headers="[__('queue.token'), __('queue.patient'), __('queue.mr'), __('queue.age'), __('queue.priority')]">
                @foreach($state['waiting'] as $visit)<tr><td class="text-lg font-semibold tabular-nums">{{ $visit->token_number }}</td><td class="text-sm font-semibold">{{ $visit->patient->name }}</td><td class="whitespace-nowrap text-xs tabular-nums">{{ $visit->patient->mr_number }}</td><td class="text-sm tabular-nums">{{ $visit->verified_age }}</td><td><x-ui.badge :status="$visit->is_priority ? 'priority' : 'confirmed'" :label="$visit->is_priority ? __('queue.priority') : __('queue.normal')" /></td></tr>@endforeach
            </x-ui.table>
        @endif
    </section>
@endif
