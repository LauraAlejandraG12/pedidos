<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            ['nombre' => 'Distribuciones El Dorado S.A.S.', 'ciudad' => 'Bogotá'],
            ['nombre' => 'Café Andino Exportadores', 'ciudad' => 'Medellín'],
            ['nombre' => 'Textiles del Valle Ltda.', 'ciudad' => 'Cali'],
            ['nombre' => 'Frutas del Caribe S.A.', 'ciudad' => 'Cartagena'],
            ['nombre' => 'Repuestos Norte', 'ciudad' => 'Bucaramanga'],
            ['nombre' => 'Alimentos del Magdalena', 'ciudad' => 'Santa Marta'],
            ['nombre' => 'Ferretería La Nariño', 'ciudad' => 'Pasto'],
            ['nombre' => 'Químicos del Meta', 'ciudad' => 'Villavicencio'],
            ['nombre' => 'Papelera Antioqueña', 'ciudad' => 'Medellín'],
            ['nombre' => 'Comercializadora Bahía', 'ciudad' => 'Barranquilla'],
            ['nombre' => 'Agroinsumos Tolima', 'ciudad' => 'Ibagué'],
            ['nombre' => 'Servicios Integrales Eje Cafetero', 'ciudad' => 'Pereira'],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create($cliente);
        }
    }
}
