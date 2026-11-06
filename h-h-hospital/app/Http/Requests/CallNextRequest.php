<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Services\DoctorQueueService;
use Illuminate\Foundation\Http\FormRequest;

class CallNextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewQueue', Appointment::class);
    }

    public function rules(): array
    {
        return DoctorQueueService::rules();
    }
}
