<?php

namespace App\Http\Requests;

use App\Services\CancellationRefundInput;
use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('refund', $this->route('payment'));
    }

    public function rules(): array
    {
        return CancellationRefundInput::rules(refund: true);
    }

    protected function prepareForValidation(): void
    {
        $this->replace(CancellationRefundInput::normalize($this->all()));
    }
}
