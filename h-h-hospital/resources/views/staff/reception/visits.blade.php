@if($visits->isEmpty())<p class="p-6 text-sm text-muted">{{ __('reception.empty') }}</p>@else
    <x-ui.table :caption="__('reception.title')" :headers="[__('reception.reference'), __('reception.patient'), __('reception.doctor'), __('reception.date'), __('reception.status'), __('reception.token'), '']">
        @foreach($visits as $visit)<tr>
            <td class="max-w-60 break-all text-xs tabular-nums">{{ $visit->public_reference }}</td>
            <td><p class="text-sm font-semibold">{{ $visit->patient?->name ?? $visit->submitted_name }}</p><p class="mt-1 text-xs text-muted">{{ $visit->patient?->mr_number ?? $visit->submitted_phone }}</p></td>
            <td class="text-xs">{{ $visit->doctorDay->doctor->user->name }}</td><td class="whitespace-nowrap text-xs">{{ $visit->doctorDay->date->format('d M Y') }}</td>
            <td><div class="flex flex-wrap gap-2"><x-ui.badge :status="$visit->status->value" />@if($visit->is_priority)<x-ui.badge status="priority" />@endif</div></td><td class="text-sm font-semibold tabular-nums">{{ $visit->token_number ?? '—' }}</td>
            <td><x-ui.button size="sm" variant="secondary" :href="route('staff.appointments.show', $visit)">{{ __('reception.open') }}</x-ui.button></td>
        </tr>@endforeach
    </x-ui.table>
@endif
