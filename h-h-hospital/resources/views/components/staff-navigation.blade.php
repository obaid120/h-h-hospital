@props(['preview' => false])
@php
    $navigationUser = $preview ? null : auth()->user();
    $upcoming = $preview ? [__('ui.nav.appointments') => 'calendar', __('ui.nav.patients') => 'users', __('ui.nav.doctors') => 'heart'] : match ($navigationUser->role) {
        \App\Enums\StaffRole::Admin => [],
        \App\Enums\StaffRole::Receptionist => [],
        \App\Enums\StaffRole::Doctor => [],
    };
@endphp
<nav {{ $attributes->class(['space-y-6']) }} aria-label="{{ __('ui.nav.workspace') }}">
    <div><p class="mb-3 px-3 text-[9px] font-semibold tracking-[.18em] text-muted">{{ __('ui.nav.workspace') }}</p>
        <div class="space-y-1">
            @if($preview)
            <a @class(['staff-nav-link', 'is-active' => request()->routeIs('design.staff')]) href="{{ route('design.staff') }}" @if(request()->routeIs('design.staff')) aria-current="page" @endif><x-icon name="grid" />{{ __('ui.nav.overview') }}</a>
            <a @class(['staff-nav-link', 'is-active' => request()->routeIs('design.components*')]) href="{{ route('design.components') }}" @if(request()->routeIs('design.components*')) aria-current="page" @endif><x-icon name="layers" />{{ __('ui.nav.components') }}</a>
            @else
                @if($navigationUser->role !== \App\Enums\StaffRole::Doctor)<a @class(['staff-nav-link', 'is-active' => request()->routeIs($navigationUser->role->homeRoute())]) href="{{ route($navigationUser->role->homeRoute()) }}" @if(request()->routeIs($navigationUser->role->homeRoute())) aria-current="page" @endif><x-icon name="grid" />{{ __('staff.nav.overview') }}</a>@endif
                @can('viewAny', \App\Models\User::class)
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.admin.users.*')]) href="{{ route('staff.admin.users.index') }}" @if(request()->routeIs('staff.admin.users.*')) aria-current="page" @endif><x-icon name="users" />{{ __('staff.nav.users') }}</a>
                @endcan
                @can('viewAny', \App\Models\Doctor::class)
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.doctors.*', 'staff.admin.doctors.*')]) href="{{ route('staff.doctors.index') }}" @if(request()->routeIs('staff.doctors.*', 'staff.admin.doctors.*')) aria-current="page" @endif><x-icon name="heart" />{{ __('doctors.title') }}</a>
                @else
                    @if($navigationUser->doctor)
                        <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.doctors.show')]) href="{{ route('staff.doctors.show', $navigationUser->doctor) }}"><x-icon name="calendar" />{{ __('doctors.schedule') }}</a>
                    @endif
                @endcan
                @can('viewAny', \App\Models\Patient::class)
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.patients.*')]) href="{{ route('staff.patients.index') }}" @if(request()->routeIs('staff.patients.*')) aria-current="page" @endif><x-icon name="users" />{{ __('patients.title') }}</a>
                @endcan
                @can('viewAny', \App\Models\Appointment::class)
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.appointments.*', 'staff.admin.appointments', 'staff.reception.*', 'staff.receipts.*')]) href="{{ route($navigationUser->isActiveAdmin() ? 'staff.admin.appointments' : 'staff.appointments.index') }}"><x-icon name="calendar" />{{ __('ui.nav.appointments') }}</a>
                @endcan
                @if($navigationUser->isActiveAdmin())
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.admin.finances')]) href="{{ route('staff.admin.finances') }}"><x-icon name="ticket" />{{ __('admin.money') }}</a>
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.admin.activity')]) href="{{ route('staff.admin.activity') }}"><x-icon name="clock" />{{ __('admin.activity') }}</a>
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.admin.settings.*')]) href="{{ route('staff.admin.settings.edit') }}"><x-icon name="layers" />{{ __('admin.settings') }}</a>
                @endif
                @can('viewQueue', \App\Models\Appointment::class)
                    <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.doctor.*')]) href="{{ route('staff.doctor.home') }}"><x-icon name="users" />{{ __('queue.title') }}</a>
                @endcan
                <a @class(['staff-nav-link', 'is-active' => request()->routeIs('staff.profile')]) href="{{ route('staff.profile', $navigationUser) }}" @if(request()->routeIs('staff.profile')) aria-current="page" @endif><x-icon name="ticket" />{{ __('staff.nav.account') }}</a>
            @endif
        </div>
    </div>
    @if(count($upcoming))<div><p class="mb-3 px-3 text-[9px] font-semibold tracking-[.18em] text-muted">{{ __('ui.nav.management') }}</p>
        <div class="space-y-1">@foreach($upcoming as $label => $icon)<span class="staff-nav-link" aria-disabled="true"><x-icon :name="$icon" /><span class="flex-1">{{ $label }}</span><span class="text-[9px]">{{ __('ui.nav.soon') }}</span></span>@endforeach</div>
    </div>@endif
    <div class="space-y-1 border-t border-line pt-5"><a class="staff-nav-link" href="{{ route('home') }}"><x-icon name="external" />{{ __('ui.nav.public') }}</a>@if(!$preview)<form method="POST" action="{{ route('staff.logout') }}" data-loading-form>@csrf<button type="submit" class="staff-nav-link w-full text-left"><x-icon name="arrow-left" />{{ __('staff.nav.logout') }}</button></form>@endif</div>
</nav>
