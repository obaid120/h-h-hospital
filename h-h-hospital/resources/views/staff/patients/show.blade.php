<x-layouts.staff :title="$patient->name" :eyebrow="__('patients.detail')" :intro="$patient->mr_number">
    <x-slot:actions><x-ui.button variant="secondary" :href="route('staff.patients.index')">{{ __('patients.back') }}</x-ui.button></x-slot:actions>
    @if($patient->is_demo)<x-ui.notification :dismissible="false" :title="__('patients.demo')">{{ __('patients.demo_hint') }}</x-ui.notification>@endif
    <section class="panel p-6 sm:p-8"><dl class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4"><div><dt class="text-xs text-muted">{{ __('patients.mr') }}</dt><dd class="mt-2 text-lg font-semibold tabular-nums">{{ $patient->mr_number }}</dd></div><div><dt class="text-xs text-muted">{{ __('patients.phone') }}</dt><dd class="mt-2 text-sm font-semibold tabular-nums">{{ $patient->phone }}</dd></div><div><dt class="text-xs text-muted">{{ __('patients.age') }}</dt><dd class="mt-2 text-sm font-semibold">{{ $patient->age_years }}</dd></div><div><dt class="text-xs text-muted">{{ __('patients.registered_at') }}</dt><dd class="mt-2 text-sm font-semibold">{{ $patient->registered_at->timezone(config('hospital.timezone'))->format('d M Y, H:i') }}</dd></div></dl><p class="mt-6 border-t border-line pt-5 text-xs leading-6 text-muted">{{ __('patients.permanent_hint') }}</p></section>
    <section class="panel overflow-hidden"><div class="panel-heading"><div><h2 class="panel-title">{{ __('patients.history') }}</h2><p class="panel-description">{{ __('patients.history_hint') }}</p></div></div>
        @if($visits->isEmpty())<x-ui.empty-state :title="__('patients.no_visits')" :description="__('patients.no_visits_hint')" icon="clock" />
        @else
            @php
                $clinicalHistory = auth()->user()->role !== \App\Enums\StaffRole::Receptionist;
                $historyHeaders = [__('patients.date'), __('patients.doctor'), __('patients.reference'), __('patients.status'), __('patients.source'), __('patients.visit_age'), __('patients.token')];
                if ($clinicalHistory) { $historyHeaders[] = __('prescription.title'); }
            @endphp
            <x-ui.table :caption="__('patients.history')" :headers="$historyHeaders">
                @foreach($visits as $visit)<tr><td class="whitespace-nowrap text-xs tabular-nums">{{ $visit->doctorDay->date->format('d M Y') }}</td><td class="min-w-40 text-xs">{{ $visit->doctorDay->doctor->user->name }}</td><td class="whitespace-nowrap text-xs font-semibold">@can('view', $visit)<a class="text-care-700 underline underline-offset-4" href="{{ route('staff.appointments.show', $visit) }}">{{ $visit->public_reference }}</a>@else{{ $visit->public_reference }}@endcan</td><td><x-ui.badge :status="$visit->status->value" /></td><td class="whitespace-nowrap text-xs">{{ __('patients.sources.'.$visit->source->value) }}</td><td class="text-xs tabular-nums">{{ $visit->verified_age ?? $visit->submitted_age }}</td><td class="text-xs tabular-nums">{{ $visit->token_number ?? '—' }}</td>@if($clinicalHistory)<td>@can('viewPrescription', $visit)@if($visit->prescription)<x-ui.button size="sm" variant="secondary" :href="route('staff.prescriptions.show', $visit->prescription)">{{ __('prescription.view') }}</x-ui.button>@elseif($visit->doctorDay->current_appointment_id === $visit->id)@can('writePrescription', $visit)<x-ui.button size="sm" variant="secondary" :href="route('staff.prescriptions.edit', $visit)">{{ __('prescription.edit') }}</x-ui.button>@endcan @endif @endcan</td>@endif</tr>@endforeach
            </x-ui.table>
            {{ $visits->links() }}
        @endif
    </section>
</x-layouts.staff>
