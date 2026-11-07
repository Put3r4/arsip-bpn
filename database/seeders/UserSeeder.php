<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('SEED_ADMIN_USERNAME', 'superadmin');
        $password = env('SEED_ADMIN_PASSWORD');

        if (empty($password)) {
            throw new \RuntimeException('Variabel lingkungan SEED_ADMIN_PASSWORD wajib dikonfigurasi di file .env.');
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make($password),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'theme' => 'samudra',
            ]
        );
    }
}
