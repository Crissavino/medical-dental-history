<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'parreseigor@gmail.com',
            'password' => Hash::make('Arreseigor93!'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
