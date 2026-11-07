<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'nip' => $data['nip'] ?? null,
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'section_id' => $data['section_id'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'theme' => $data['theme'] ?? 'samudra',
        ]);
    }
}
