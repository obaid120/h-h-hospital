@props(['doctor'])
@if($doctor->photo_path)
    <img {{ $attributes->class(['shrink-0 rounded-xl object-cover']) }} src="{{ route('doctors.photo', $doctor) }}" alt="{{ __('doctors.photo_alt', ['name' => $doctor->user->name]) }}" loading="lazy" width="160" height="160">
@else
    <span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-xl bg-care-50 font-semibold text-care-700']) }} aria-hidden="true">{{ $doctor->user->initials() }}</span>
@endif
