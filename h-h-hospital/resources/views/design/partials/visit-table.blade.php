<div class="panel overflow-hidden">
    <div class="panel-heading"><div><h2 class="panel-title">{{ __('ui.staff.table_title') }}</h2><p class="panel-description">{{ __('ui.staff.table_description') }}</p></div><span class="text-[11px] text-muted">{{ __('ui.staff.preview') }}</span></div>
    <x-ui.table :caption="__('ui.staff.all_visits')" :headers="[__('ui.staff.patient'), __('ui.staff.doctor'), __('ui.staff.status'), __('ui.staff.visit_type'), __('ui.staff.actions')]">
        @foreach($visits as $visit)
            <tr>
                <td><div class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-care-50 text-[10px] font-semibold text-care-700">{{ $visit['initials'] }}</span><div><p class="whitespace-nowrap text-xs font-semibold">{{ $visit['name'] }}</p><p class="mt-1 text-[10px] text-muted">{{ $visit['reference'] }}</p></div></div></td>
                <td class="whitespace-nowrap text-xs text-muted">{{ $visit['doctor'] }}</td>
                <td><x-ui.badge :status="$visit['status']" /></td>
                <td><x-ui.badge :status="$visit['type']" /></td>
                <td><button type="button" class="icon-button" data-open-dialog="visit-{{ $visit['reference'] }}" aria-haspopup="dialog" aria-controls="visit-{{ $visit['reference'] }}" aria-label="{{ __('ui.actions.view_for', ['name' => $visit['name']]) }}"><x-icon name="arrow-right" class="size-4" /></button></td>
            </tr>
        @endforeach
    </x-ui.table>
    {{ $visits->links() }}
</div>
@foreach($visits as $visit)
    <x-ui.dialog :id="'visit-'.$visit['reference']" :title="__('ui.staff.details_title')" :description="__('ui.staff.visit_note')">
        <div class="mb-5 flex items-center gap-4"><span class="flex size-12 items-center justify-center rounded-full bg-care-50 text-sm font-semibold text-care-700">{{ $visit['initials'] }}</span><div><h3 class="font-semibold">{{ $visit['name'] }}</h3><p class="mt-1 text-xs text-muted">{{ $visit['doctor'] }}</p></div></div>
        <dl class="space-y-4 rounded-xl border border-line bg-canvas p-4 text-sm"><div class="flex flex-wrap justify-between gap-2"><dt class="text-muted">{{ __('ui.staff.reference_label') }}</dt><dd class="font-medium">{{ $visit['reference'] }}</dd></div><div class="flex items-center justify-between"><dt class="text-muted">{{ __('ui.staff.status') }}</dt><dd><x-ui.badge :status="$visit['status']" /></dd></div></dl>
        <x-slot:footer><x-ui.button variant="secondary" data-close-dialog>{{ __('ui.actions.close') }}</x-ui.button></x-slot:footer>
    </x-ui.dialog>
@endforeach
