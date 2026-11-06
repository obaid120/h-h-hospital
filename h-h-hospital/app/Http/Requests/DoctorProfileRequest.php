<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('doctor')
            ? $this->user()->can('update', $this->route('doctor'))
            : $this->user()->can('create', Doctor::class);
    }

    public function rules(): array
    {
        $creating = ! $this->route('doctor');

        return [
            'account_mode' => [$creating ? 'required' : 'exclude', Rule::in(['new', 'existing'])],
            'user_id' => [Rule::excludeIf(! $creating || $this->input('account_mode') !== 'existing'), 'required', 'integer', Rule::exists(User::class, 'id')->where('role', StaffRole::Doctor->value), Rule::unique(Doctor::class)],
            'name' => [Rule::excludeIf(! $creating || $this->input('account_mode') !== 'new'), 'required', 'string', 'max:100'],
            'email' => [Rule::excludeIf(! $creating || $this->input('account_mode') !== 'new'), 'required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => [Rule::excludeIf(! $creating || $this->input('account_mode') !== 'new'), 'required', 'string', 'max:72', Password::min(12)->mixedCase()->numbers(), 'confirmed'],
            'specialization' => ['required', 'string', 'max:120'],
            'biography' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'normal_fee' => ['required', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,5})(?:\.[0-9]{1,2})?\z/'],
            'priority_surcharge' => ['required', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,5})(?:\.[0-9]{1,2})?\z/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }
}
