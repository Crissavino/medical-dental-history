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
            'name' => 'Admin User',
            'email' => 'admin@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Dr. Maria Popescu',
            'email' => 'maria@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'dentist',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Dr. Andrei Ionescu',
            'email' => 'andrei@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'dentist',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Elena Vasile',
            'email' => 'elena@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'assistant',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Ana Dumitrescu',
            'email' => 'ana@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
            'email_verified_at' => now(),
        ]);
    }
}
