<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Services\PatientPhone;
use App\Services\PatientRegistrar;
use Illuminate\Foundation\Http\FormRequest;

class PatientRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Patient::class);
    }

    public function rules(): array
    {
        return PatientRegistrar::rules();
    }

    public function messages(): array
    {
        return ['phone.regex' => __('patients.phone_error'), 'phone.required' => __('patients.phone_error')];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => PatientPhone::normalize($this->input('phone')) ?? $this->input('phone')]);
        }
    }
}
