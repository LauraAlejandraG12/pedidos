<?php

namespace Database\Factories;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'total' => fake()->randomFloat(2, 50_000, 4_000_000),
            'fecha' => fake()->dateTimeBetween('-20 days', 'now')->format('Y-m-d'),
            'estado' => EstadoPedido::Pendiente,
        ];
    }

    public function despachado(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoPedido::Despachado,
            'costo_envio' => fake()->randomFloat(2, 12_000, 35_000),
            'despachado_at' => now()->subDay(),
        ]);
    }

    public function entregado(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoPedido::Entregado,
            'costo_envio' => fake()->randomFloat(2, 12_000, 35_000),
            'despachado_at' => now()->subDays(2),
            'entregado_at' => now()->subDay(),
        ]);
    }
}
