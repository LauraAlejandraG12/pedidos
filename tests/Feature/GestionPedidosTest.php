<?php

namespace Tests\Feature;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GestionPedidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Si algún test llega a hacer una petición HTTP sin fake, falla en
        // vez de tocar la transportadora real (el stub del compose es
        // alcanzable desde el contenedor de tests).
        Http::preventStrayRequests();
    }

    /**
     * La API de la transportadora responde con una cotización válida.
     *
     * Nota: Http::fake() acumula stubs y gana el primero que calza, así que
     * esto se registra por test y no en setUp (si no, un fake de error de
     * este archivo nunca aplicaría).
     */
    private function transportadoraRespondeOk(): void
    {
        Http::fake([
            '*' => Http::response([
                'cotizacion_id' => 'COT123',
                'costo_envio' => 18500,
                'moneda' => 'COP',
                'eta_dias' => 2,
            ], 200),
        ]);
    }

    private function operador(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERADOR]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ---------------------------------------------------------------- crear

    public function test_crear_un_pedido_desde_el_formulario(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->operador())
            ->post('/pedidos', [
                'cliente_id' => $cliente->id,
                'total' => 250000,
                'fecha' => now()->toDateString(),
            ])
            ->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseHas('pedidos', [
            'cliente_id' => $cliente->id,
            'total' => 250000,
            'estado' => EstadoPedido::Pendiente,
        ]);
    }

    public function test_valida_los_datos_del_pedido(): void
    {
        $this->actingAs($this->operador())
            ->post('/pedidos', ['cliente_id' => 999, 'total' => 0, 'fecha' => ''])
            ->assertSessionHasErrors(['cliente_id', 'total', 'fecha']);
    }

    // ------------------------------------------------------------ despachar

    public function test_al_despachar_se_cotiza_el_envio_y_se_guarda(): void
    {
        $this->transportadoraRespondeOk();

        $cliente = Cliente::factory()->create(['ciudad' => 'Cali']);
        $pedido = Pedido::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoPedido::Pendiente,
            'total' => 500000,
        ]);

        $this->actingAs($this->operador())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'despachado'])
            ->assertSessionHasNoErrors();

        $pedido->refresh();

        $this->assertSame(EstadoPedido::Despachado, $pedido->estado);
        $this->assertSame(18500.0, (float) $pedido->costo_envio);
        $this->assertNotNull($pedido->despachado_at);

        // Se llamó a la transportadora con la credencial y la ciudad destino.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/cotizar')
                && $request['destino'] === 'Cali'
                && $request->hasHeader('Authorization', 'Bearer '.config('services.transportadora.key'));
        });
    }

    public function test_si_la_transportadora_falla_no_se_despacha(): void
    {
        Http::fake(['*' => Http::response(['error' => 'sin_cupo'], 500)]);

        $pedido = Pedido::factory()->create(['estado' => EstadoPedido::Pendiente]);

        $this->actingAs($this->operador())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'despachado'])
            ->assertSessionHasErrors(['estado']);

        $pedido->refresh();

        // Nada cambió: sin estado nuevo y sin costo de envío inventado.
        $this->assertSame(EstadoPedido::Pendiente, $pedido->estado);
        $this->assertNull($pedido->costo_envio);
        $this->assertNull($pedido->despachado_at);
    }

    public function test_al_entregar_se_registra_la_fecha(): void
    {
        $pedido = Pedido::factory()->despachado()->create();

        $this->actingAs($this->operador())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'entregado'])
            ->assertSessionHasNoErrors();

        $pedido->refresh();

        $this->assertSame(EstadoPedido::Entregado, $pedido->estado);
        $this->assertNotNull($pedido->entregado_at);
    }

    // -------------------------------------------------- inmutabilidad

    public function test_un_pedido_entregado_no_se_puede_modificar_como_operador(): void
    {
        $pedido = Pedido::factory()->entregado()->create(['total' => 1000]);

        $this->actingAs($this->operador())
            ->put("/pedidos/{$pedido->id}", [
                'cliente_id' => $pedido->cliente_id,
                'total' => 999999,
                'fecha' => $pedido->fecha->toDateString(),
            ])
            ->assertSessionHasErrors(['pedido']);

        $this->assertSame(1000.0, (float) $pedido->refresh()->total);
    }

    public function test_un_pedido_entregado_no_se_puede_cambiar_de_estado_como_operador(): void
    {
        $pedido = Pedido::factory()->entregado()->create();

        $this->actingAs($this->operador())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'pendiente'])
            ->assertSessionHasErrors(['estado']);

        $this->assertSame(EstadoPedido::Entregado, $pedido->refresh()->estado);
    }

    public function test_un_operador_no_puede_regresar_un_pedido_a_pendiente(): void
    {
        $pedido = Pedido::factory()->despachado()->create();

        $this->actingAs($this->operador())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'pendiente'])
            ->assertSessionHasErrors(['estado']);

        $this->assertSame(EstadoPedido::Despachado, $pedido->refresh()->estado);
    }

    // ---------------------------------------------------- corrección admin

    public function test_el_admin_puede_corregir_un_pedido_entregado(): void
    {
        $pedido = Pedido::factory()->entregado()->create(['costo_envio' => 20000]);

        $this->actingAs($this->admin())
            ->post("/pedidos/{$pedido->id}/estado", ['estado' => 'pendiente'])
            ->assertSessionHasNoErrors();

        $pedido->refresh();

        // Al volver a pendiente se limpia el rastro del despacho.
        $this->assertSame(EstadoPedido::Pendiente, $pedido->estado);
        $this->assertNull($pedido->costo_envio);
        $this->assertNull($pedido->despachado_at);
        $this->assertNull($pedido->entregado_at);
    }

    public function test_el_admin_puede_editar_el_total_de_un_pedido_entregado(): void
    {
        $pedido = Pedido::factory()->entregado()->create(['total' => 1000]);

        $this->actingAs($this->admin())
            ->put("/pedidos/{$pedido->id}", [
                'cliente_id' => $pedido->cliente_id,
                'total' => 7500,
                'fecha' => $pedido->fecha->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(7500.0, (float) $pedido->refresh()->total);
    }

    // ------------------------------------------------------------------ API

    public function test_api_cambia_el_estado_con_token_de_sanctum(): void
    {
        $this->transportadoraRespondeOk();

        $pedido = Pedido::factory()->create(['estado' => EstadoPedido::Pendiente]);
        Sanctum::actingAs($this->operador());

        $this->patchJson("/api/pedidos/{$pedido->id}/estado", ['estado' => 'despachado'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'despachado');

        $this->assertEquals(18500, $this->getJson("/api/pedidos/{$pedido->id}")->json('data.costo_envio'));
    }

    public function test_api_devuelve_422_si_un_operador_quiere_modificar_un_entregado(): void
    {
        $pedido = Pedido::factory()->entregado()->create();
        Sanctum::actingAs($this->operador());

        $this->patchJson("/api/pedidos/{$pedido->id}/estado", ['estado' => 'pendiente'])
            ->assertStatus(422);

        $this->assertSame(EstadoPedido::Entregado, $pedido->refresh()->estado);
    }

    public function test_api_requiere_autenticacion_para_cambiar_el_estado(): void
    {
        $pedido = Pedido::factory()->create();

        $this->patchJson("/api/pedidos/{$pedido->id}/estado", ['estado' => 'despachado'])
            ->assertStatus(401);
    }
}
