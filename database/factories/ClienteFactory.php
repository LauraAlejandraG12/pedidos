<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'ciudad' => fake()->randomElement([
                'Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga',
                'Cartagena', 'Santa Marta', 'Pasto', 'Ibagué', 'Manizales',
                'Pereira', 'Neiva', 'Montería', 'Valledupar', 'Villavicencio',
            ]),
        ];
    }
}
