<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('user')
            ? $this->user()->can('update', $this->route('user'))
            : $this->user()->can('create', User::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->route('user'))],
            'role' => ['required', Rule::enum(StaffRole::class)],
            'is_active' => ['required', 'boolean'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'max:72', Password::min(12)->mixedCase()->numbers(), 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }
}
