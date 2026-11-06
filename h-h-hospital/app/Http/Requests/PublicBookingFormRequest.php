<?php

namespace App\Http\Requests;

class PublicBookingFormRequest extends OnlineBookingRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['doctor_id' => $this->route('doctor')->id]);
    }

    protected function getRedirectUrl(): string
    {
        return route('booking.create', $this->route('doctor'));
    }
}
