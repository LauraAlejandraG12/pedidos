<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Ana Admin',
            'email' => 'admin@uva3.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        User::create([
            'name' => 'Carlos Operador',
            'email' => 'operador@uva3.test',
            'password' => 'password',
            'role' => User::ROLE_OPERADOR,
        ]);
    }
}
