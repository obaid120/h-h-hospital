<?php

namespace App\Http\Requests;

use App\Services\BookingInput;
use Illuminate\Foundation\Http\FormRequest;

class OnlineBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return BookingInput::rules();
    }

    public function messages(): array
    {
        return ['phone.regex' => __('patients.phone_error')];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(BookingInput::normalize($this->all()));
    }
}
