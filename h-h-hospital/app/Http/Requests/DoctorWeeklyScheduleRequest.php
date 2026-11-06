<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoctorWeeklyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('doctor'));
    }

    public function rules(): array
    {
        $rules = ['days' => ['required', 'array:1,2,3,4,5,6,7', 'size:7']];

        foreach (range(1, 7) as $weekday) {
            $prefix = 'days.'.$weekday;
            $rules[$prefix] = ['required', 'array:enabled,opens_at,closes_at'];
            $rules[$prefix.'.enabled'] = ['required', 'boolean'];
            $rules[$prefix.'.opens_at'] = ['exclude_unless:'.$prefix.'.enabled,1', 'required', 'date_format:H:i'];
            $rules[$prefix.'.closes_at'] = ['exclude_unless:'.$prefix.'.enabled,1', 'required', 'date_format:H:i', 'after:'.$prefix.'.opens_at'];
        }

        return $rules;
    }
}
