<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('VAPT_SEED_PASSWORD');
        if (! app()->environment('local') || ! is_string($password) || strlen($password) < 16) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => env('VAPT_SEED_EMAIL', 'analyst@vapt.local')],
            [
                'name' => 'Lead Security Analyst',
                'password' => Hash::make($password),
            ]
        );
        // role/is_active/email_verified_at are not mass-assignable (see App\Models\User), set explicitly.
        $user->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
