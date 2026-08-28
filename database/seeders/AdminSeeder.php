<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@controleobra.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'ajuda@controleobra.test'],
            [
                'name' => 'Ajudante',
                'password' => Hash::make('password'),
                'role' => User::ROLE_AJUDANTE,
                'email_verified_at' => now(),
            ]
        );
    }
}
