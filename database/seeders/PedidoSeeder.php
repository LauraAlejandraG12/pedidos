<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Database\Seeder;

class PedidoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::all();

        if ($clientes->isEmpty()) {
            return;
        }

        // Pedidos del mes actual para el reporte: se reparten entre los
        // clientes para que el ranking tenga de todo.
        $clientes->each(function (Cliente $cliente, int $indice) {
            $cantidad = 1 + ($indice % 4); // 1 a 4 pedidos por cliente

            for ($i = 0; $i < $cantidad; $i++) {
                $estado = match ($i % 3) {
                    0 => 'pendiente',
                    1 => 'despachado',
                    default => 'entregado',
                };

                $pedido = Pedido::factory()->create([
                    'cliente_id' => $cliente->id,
                    'fecha' => now()->subDays($i * 7)->toDateString(),
                    'estado' => $estado,
                ]);

                if ($estado === 'despachado') {
                    $pedido->forceFill(['costo_envio' => 15000 + ($i * 1500), 'despachado_at' => now()->subDay()])->save();
                }

                if ($estado === 'entregado') {
                    $pedido->forceFill([
                        'costo_envio' => 15000 + ($i * 1500),
                        'despachado_at' => now()->subDays(3),
                        'entregado_at' => now()->subDay(),
                    ])->save();
                }
            }
        });

        // Algunos pedidos de meses anteriores: NO deben aparecer en el
        // reporte del mes actual.
        Pedido::factory()->count(10)->create([
            'fecha' => now()->subMonth()->subDays(rand(1, 25))->toDateString(),
        ]);

        Pedido::factory()->count(8)->entregado()->create([
            'fecha' => now()->subMonths(2)->subDays(rand(1, 25))->toDateString(),
        ]);
    }
}
