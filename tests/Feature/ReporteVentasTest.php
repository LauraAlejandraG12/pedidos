<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteVentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_suma_lo_vendido_por_cliente_en_el_mes_actual_ordenado_desc(): void
    {
        $ana = Cliente::factory()->create(['nombre' => 'Ana']);
        $bruno = Cliente::factory()->create(['nombre' => 'Bruno']);
        $carla = Cliente::factory()->create(['nombre' => 'Carla']);

        // Ana: dos pedidos este mes que suman 300.000.
        Pedido::factory()->create(['cliente_id' => $ana->id, 'total' => 200_000, 'fecha' => now()->toDateString()]);
        Pedido::factory()->create(['cliente_id' => $ana->id, 'total' => 100_000, 'fecha' => now()->subDays(3)]);

        // Bruno: un solo pedido de 250.000.
        Pedido::factory()->create(['cliente_id' => $bruno->id, 'total' => 250_000, 'fecha' => now()->subDay()]);

        // Carla: 50.000.
        Pedido::factory()->create(['cliente_id' => $carla->id, 'total' => 50_000, 'fecha' => now()->subDays(10)]);

        // Pedidos de meses anteriores: no cuentan.
        Pedido::factory()->create(['cliente_id' => $ana->id, 'total' => 999_999, 'fecha' => now()->subMonth()->toDateString()]);
        Pedido::factory()->create(['cliente_id' => $bruno->id, 'total' => 999_999, 'fecha' => now()->subMonths(2)->toDateString()]);

        $respuesta = $this->getJson('/api/reporte/ventas')->assertOk();

        $filas = $respuesta->json('filas');

        // Los totales llegan como números de JSON; PHP serializa 300000.0 como
        // 300000, así que se comparan como float explícitos (identidad de tipo).
        $this->assertSame(['Ana', 'Bruno', 'Carla'], array_column($filas, 'cliente'));
        $this->assertSame(
            [300_000.0, 250_000.0, 50_000.0],
            array_map('floatval', array_column($filas, 'total_vendido')),
        );
        $this->assertSame([2, 1, 1], array_column($filas, 'pedidos'));
        $this->assertSame(600_000.0, (float) $respuesta->json('total_general'));
        $this->assertSame(1, $filas[0]['puesto']);
    }

    public function test_los_clientes_sin_ventas_en_el_mes_no_aparecen(): void
    {
        Cliente::factory()->create(['nombre' => 'SinVentas']);

        $respuesta = $this->getJson('/api/reporte/ventas')->assertOk();

        $this->assertSame([], $respuesta->json('filas'));
        $this->assertSame(0.0, (float) $respuesta->json('total_general'));
    }

    public function test_el_reporte_tambien_se_muestra_en_la_interfaz_web(): void
    {
        $cliente = Cliente::factory()->create();
        Pedido::factory()->create(['cliente_id' => $cliente->id, 'fecha' => now()->toDateString()]);

        $this->actingAs(\App\Models\User::factory()->create())
            ->get('/reportes/ventas')
            ->assertOk()
            ->assertSee('Total vendido por cliente')
            ->assertSee($cliente->nombre);
    }

    public function test_el_listado_web_filtra_por_estado(): void
    {
        Pedido::factory()->count(2)->create(['estado' => 'pendiente']);
        Pedido::factory()->create(['estado' => 'despachado']);

        $this->actingAs(\App\Models\User::factory()->create())
            ->get('/pedidos?estado=despachado')
            ->assertOk()
            ->assertSee('Despachado');

        $this->actingAs(\App\Models\User::factory()->create())
            ->get('/pedidos?estado=algo_invalido')
            ->assertSessionHasErrors(['estado']);
    }
}
