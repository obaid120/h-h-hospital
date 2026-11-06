<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorDateOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('doctor'));
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now(config('hospital.timezone'))->toDateString(), 'before_or_equal:'.now(config('hospital.timezone'))->addYear()->toDateString()],
            'schedule_override' => ['required', Rule::in(['weekly', 'open', 'closed'])],
            'opens_at' => ['exclude_unless:schedule_override,open', 'required', 'date_format:H:i'],
            'closes_at' => ['exclude_unless:schedule_override,open', 'required', 'date_format:H:i', 'after:opens_at'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
