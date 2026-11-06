<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', StaffRole::Admin)->where('is_active', true)->exists()) {
            return;
        }

        $credentials = config('staff.initial_admin');
        $credentials['email'] = Str::lower(trim((string) ($credentials['email'] ?? '')));
        Validator::make($credentials, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:72', Password::min(12)->mixedCase()->numbers()],
        ])->validate();

        DB::transaction(function () use ($credentials): void {
            $users = User::query()->orderBy('id')->lockForUpdate()->get();

            if ($users->contains(fn (User $user): bool => $user->isActiveAdmin())) {
                return;
            }

            if ($users->contains('email', $credentials['email'])) {
                throw new RuntimeException('The bootstrap email already belongs to an account. It will not be promoted or overwritten.');
            }

            User::create([...$credentials, 'role' => StaffRole::Admin, 'is_active' => true]);
        }, attempts: 3);
    }
}
