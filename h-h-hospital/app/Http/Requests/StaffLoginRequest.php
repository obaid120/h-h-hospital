<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StaffLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255'], 'password' => ['required', 'string', 'max:72']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function throttleKey(): string
    {
        return 'staff-login:'.hash('sha256', $this->string('email').'|'.$this->ip());
    }

    public function ipThrottleKey(): string
    {
        return 'staff-login-ip:'.hash('sha256', (string) $this->ip());
    }
}
