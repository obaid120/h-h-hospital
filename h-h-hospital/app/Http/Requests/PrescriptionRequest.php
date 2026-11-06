<?php

namespace App\Http\Requests;

use App\Services\PrescriptionInput;
use Illuminate\Foundation\Http\FormRequest;

class PrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('staff.prescriptions.complete') ? 'completeOpd' : 'writePrescription', $this->route('appointment'));
    }

    public function rules(): array
    {
        return PrescriptionInput::rules($this->routeIs('staff.prescriptions.complete'));
    }

    protected function prepareForValidation(): void
    {
        $this->replace(PrescriptionInput::normalize($this->all(), $this->routeIs('staff.prescriptions.complete')));
    }
}
