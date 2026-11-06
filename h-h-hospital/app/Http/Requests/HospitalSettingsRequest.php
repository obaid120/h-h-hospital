<?php

namespace App\Http\Requests;

use App\Services\HospitalSettings;
use Illuminate\Foundation\Http\FormRequest;

class HospitalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isActiveAdmin();
    }

    public function rules(): array
    {
        return HospitalSettings::rules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(HospitalSettings::normalize($this->all()));
    }
}
