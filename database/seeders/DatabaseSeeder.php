<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Datos de prueba del módulo de pedidos.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ClienteSeeder::class,
            PedidoSeeder::class,
        ]);
    }
}
