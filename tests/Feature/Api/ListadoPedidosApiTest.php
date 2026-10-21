<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListadoPedidosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_todos_los_pedidos(): void
    {
        Pedido::factory()->count(3)->create();

        $respuesta = $this->getJson('/api/pedidos');

        $respuesta->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'cliente' => ['id', 'nombre', 'ciudad'], 'total', 'costo_envio', 'fecha', 'estado']],
                'links',
                'meta',
            ]);
    }

    public function test_filtra_por_estado_pendiente(): void
    {
        Pedido::factory()->count(2)->create(['estado' => EstadoPedido::Pendiente]);
        Pedido::factory()->create(['estado' => EstadoPedido::Despachado]);
        Pedido::factory()->entregado()->create();

        $respuesta = $this->getJson('/api/pedidos?estado=pendiente');

        $respuesta->assertOk()->assertJsonCount(2, 'data');

        foreach ($respuesta->json('data') as $pedido) {
            $this->assertSame('pendiente', $pedido['estado']);
        }
    }

    public function test_filtra_por_estado_despachado_y_entregado(): void
    {
        Pedido::factory()->count(2)->despachado()->create();
        Pedido::factory()->entregado()->create();

        $this->getJson('/api/pedidos?estado=despachado')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/pedidos?estado=entregado')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_rechaza_un_estado_invalido(): void
    {
        Pedido::factory()->create();

        $this->getJson('/api/pedidos?estado=cancelado')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    public function test_devuelve_el_detalle_de_un_pedido(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Distribuciones El Dorado', 'ciudad' => 'Bogotá']);
        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'total' => 150000,
            'fecha' => '2026-10-15',
            'estado' => EstadoPedido::Pendiente,
        ]);

        // json_encode serializa 150000.0 como 150000, por eso se compara con
        // assertEquals (equivalencia numérica) y no assertSame (identidad de tipo).
        $this->getJson("/api/pedidos/{$pedido->id}")
            ->assertOk()
            ->assertJsonPath('data.cliente.nombre', 'Distribuciones El Dorado')
            ->assertJsonPath('data.cliente.ciudad', 'Bogotá')
            ->assertJsonPath('data.fecha', '2026-10-15')
            ->assertJsonPath('data.estado', 'pendiente');

        $this->assertEquals(150000, $this->getJson("/api/pedidos/{$pedido->id}")->json('data.total'));
    }
}
