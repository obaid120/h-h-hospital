<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Services\ReceptionInput;
use Illuminate\Foundation\Http\FormRequest;

class ReceptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function rules(): array
    {
        return ReceptionInput::rules($this->routeIs('staff.reception.walk-in.store'));
    }

    public function attributes(): array
    {
        return ['patient_id' => 'verified patient', 'doctor_id' => 'doctor', 'age_years' => 'verified age', 'patient_mode' => 'patient identity option', 'identity_confirmed' => 'identity verification', 'cash_received' => 'cash received confirmation'];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('patients.phone_error')];
    }

    protected function prepareForValidation(): void
    {
        $this->replace(ReceptionInput::normalize($this->all()));
    }
}
