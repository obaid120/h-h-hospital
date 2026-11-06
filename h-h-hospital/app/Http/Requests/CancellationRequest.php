<?php

namespace App\Http\Requests;

use App\Services\CancellationRefundInput;
use Illuminate\Foundation\Http\FormRequest;

class CancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('appointment'));
    }

    public function rules(): array
    {
        return CancellationRefundInput::rules();
    }

    protected function prepareForValidation(): void
    {
        $this->replace(CancellationRefundInput::normalize($this->all()));
    }
}
