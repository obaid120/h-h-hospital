<x-layouts.staff :title="$doctor->user->name" :eyebrow="__('doctors.title')" :intro="$doctor->specialization">
    <x-slot:actions>@can('viewAny', \App\Models\Doctor::class)<x-ui.button variant="secondary" :href="route('staff.doctors.index')">{{ __('doctors.back') }}</x-ui.button>@endcan @can('update', $doctor)<x-ui.button :href="route('staff.admin.doctors.edit', $doctor)">{{ __('doctors.edit') }}</x-ui.button>@endcan</x-slot:actions>
    @if(!$doctor->is_active)<x-ui.notification variant="warning" :dismissible="false">{{ __('doctors.profile_inactive') }}</x-ui.notification>@endif
    @if(!$doctor->user->is_active)<x-ui.notification variant="warning" :dismissible="false">{{ __('doctors.account_inactive') }}</x-ui.notification>@endif
    <section class="panel flex flex-wrap items-center gap-6 p-6"><x-doctor-photo :doctor="$doctor" class="size-24 text-2xl" /><div class="min-w-0 flex-1"><p class="text-sm leading-6 text-muted break-words">{{ $doctor->biography }}</p><p class="mt-3 text-sm font-semibold">{{ __('doctors.fee') }}: {{ \App\Models\Doctor::feeDisplay($doctor->normal_fee_minor) }}</p><p class="mt-2 text-xs text-muted">{{ __('doctors.surcharge') }}: {{ \App\Models\Doctor::feeDisplay($doctor->priority_surcharge_minor) }}</p></div></section>
    <div class="grid items-start gap-6 xl:grid-cols-2">
        <section class="panel p-6 sm:p-7"><h2 class="panel-title">{{ __('doctors.weekly') }}</h2><p class="mt-2 text-xs leading-5 text-muted">{{ __('doctors.weekly_hint', ['timezone' => config('hospital.timezone')]) }}</p>
            @can('update', $doctor)
                <form method="POST" action="{{ route('staff.admin.doctors.weekly', $doctor) }}" class="mt-6 space-y-5" data-loading-form novalidate>
                    @csrf @method('PUT')
                    @if($errors->has('days*'))<x-ui.notification variant="error" :dismissible="false">{{ __('ui.library.errors') }} {{ $errors->first('days') }}</x-ui.notification>@endif
                    @foreach(__('doctors.weekdays') as $weekday => $label)
                        @php($hours = $doctor->weeklySchedules->firstWhere('weekday', $weekday))
                        <fieldset class="rounded-xl border border-line p-4"><legend class="px-2 text-xs font-semibold">{{ $label }}</legend>
                            <div class="grid gap-3 sm:grid-cols-3"><x-form.select :name="'days['.$weekday.'][enabled]'" :id="'day-'.$weekday.'-enabled'" :label="__('doctors.working')" :options="['0' => __('doctors.off'), '1' => __('doctors.open')]" :value="(int) (bool) $hours" :error="$errors->first('days.'.$weekday.'.enabled')" />
                                <x-form.input :name="'days['.$weekday.'][opens_at]'" :id="'day-'.$weekday.'-opens'" type="time" :label="__('doctors.opens')" :value="$hours ? substr($hours->opens_at, 0, 5) : ''" :error="$errors->first('days.'.$weekday.'.opens_at')" />
                                <x-form.input :name="'days['.$weekday.'][closes_at]'" :id="'day-'.$weekday.'-closes'" type="time" :label="__('doctors.closes')" :value="$hours ? substr($hours->closes_at, 0, 5) : ''" :error="$errors->first('days.'.$weekday.'.closes_at')" />
                            </div>
                        </fieldset>
                    @endforeach
                    <x-ui.button type="submit">{{ __('doctors.save_weekly') }}</x-ui.button>
                </form>
            @else
                <dl class="mt-6 space-y-3 text-sm">@foreach(__('doctors.weekdays') as $weekday => $label)@php($hours = $doctor->weeklySchedules->firstWhere('weekday', $weekday))<div class="flex justify-between gap-4 border-b border-line pb-3"><dt>{{ $label }}</dt><dd>{{ $hours ? substr($hours->opens_at, 0, 5).' – '.substr($hours->closes_at, 0, 5) : __('doctors.off') }}</dd></div>@endforeach</dl>
            @endcan
        </section>
        @can('update', $doctor)
            <section class="panel p-6 sm:p-7"><h2 class="panel-title">{{ __('doctors.override') }}</h2><p class="mt-2 text-xs leading-6 text-muted">{{ __('doctors.override_hint') }}</p>
                <form method="POST" action="{{ route('staff.admin.doctors.override', $doctor) }}" class="mt-6 space-y-5" data-loading-form novalidate>
                    @csrf @method('PUT')
                    <x-form.input name="date" type="date" :label="__('doctors.date')" :value="$from->toDateString()" :min="now(config('hospital.timezone'))->toDateString()" :max="now(config('hospital.timezone'))->addYear()->toDateString()" :required="true" />
                    <x-form.select name="schedule_override" :label="__('doctors.mode')" :options="__('doctors.modes')" value="closed" :required="true" />
                    <div class="grid gap-4 sm:grid-cols-2"><x-form.input name="opens_at" type="time" :label="__('doctors.opens')" /><x-form.input name="closes_at" type="time" :label="__('doctors.closes')" /></div>
                    <x-form.textarea name="reason" :label="__('doctors.reason')" maxlength="500" rows="3" :required="true" />
                    <x-ui.button type="submit">{{ __('doctors.save_override') }}</x-ui.button>
                </form>
            </section>
        @endcan
    </div>
    <section class="panel overflow-hidden"><div class="panel-heading"><div><h2 class="panel-title">{{ __('doctors.calendar') }}</h2><p class="panel-description">{{ __('doctors.calendar_hint') }}</p></div></div>
        <form method="GET" action="{{ route('staff.doctors.show', $doctor) }}" class="flex flex-wrap items-end gap-4 border-b border-line p-6"><x-form.input name="from" type="date" :label="__('doctors.from')" :value="$from->toDateString()" /><x-ui.button type="submit" variant="secondary">{{ __('staff.users.filter') }}</x-ui.button></form>
        <x-ui.table :caption="__('doctors.calendar')" :headers="[__('doctors.date'), __('doctors.hours'), __('doctors.reserved'), __('doctors.last_token'), __('doctors.note')]">
            @foreach($calendar as $entry)
                <tr><td class="whitespace-nowrap text-xs font-semibold">{{ $entry['date'] }}</td><td class="whitespace-nowrap text-xs">{{ $entry['hours'] ? $entry['hours']['opens_at'].' – '.$entry['hours']['closes_at'] : __('doctors.closed') }}<p class="mt-1 text-muted">{{ $entry['day']?->schedule_override === 'open' ? __('doctors.special_rule') : ($entry['day']?->schedule_override === 'closed' ? __('doctors.closed') : __('doctors.weekly_rule')) }}</p></td>
                    <td class="text-xs tabular-nums">{{ $entry['day']?->used_capacity ?? 0 }} / {{ \App\Models\DoctorDay::CAPACITY }}@if(($entry['day']?->used_capacity ?? 0) >= 50)<p class="mt-1 font-semibold">{{ __('doctors.full') }}</p>@endif</td><td class="text-xs tabular-nums">{{ $entry['day']?->last_token ?? 0 }}</td><td class="min-w-52 max-w-80 text-xs leading-5 break-words">{{ $entry['day']?->reason }}@if(!$entry['hours'] && ($entry['day']?->used_capacity ?? 0) > 0)<p class="mt-2 font-semibold text-amber-800">{{ __('doctors.retained') }}</p>@endif</td>
                </tr>
            @endforeach
        </x-ui.table>
    </section>
</x-layouts.staff>
