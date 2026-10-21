<?php

namespace App\Services;

use App\Enums\EstadoPedido;
use App\Exceptions\PedidoInmutableException;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Transportadora\CotizacionException;
use App\Services\Transportadora\TransportadoraClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Todas las reglas del módulo de pedidos viven acá:
 *
 *  - Un pedido entregado es inmutable: solo el administrador lo corrige.
 *  - Al marcar un pedido como "despachado" se cotiza el envío contra la API
 *    de la transportadora y se guarda en `costo_envio`.
 *  - Si la cotización falla, el pedido NO cambia de estado (502).
 *  - Retroceder el estado (corrección) solo lo hace el administrador.
 */
class GestorPedidos
{
    public function __construct(private readonly TransportadoraClient $transportadora)
    {
    }

    public function crear(array $datos): Pedido
    {
        return Pedido::create([
            'cliente_id' => $datos['cliente_id'],
            'total' => $datos['total'],
            'fecha' => $datos['fecha'] ?? now()->toDateString(),
            'estado' => EstadoPedido::Pendiente,
        ]);
    }

    public function actualizar(Pedido $pedido, array $datos, User $usuario): Pedido
    {
        $this->asegurarModificable($pedido, $usuario);

        $pedido->fill($datos);
        $pedido->save();

        return $pedido->refresh();
    }

    public function cambiarEstado(
        Pedido $pedido,
        EstadoPedido $nuevoEstado,
        User $usuario,
        ?string $observaciones = null,
    ): Pedido {
        $this->asegurarModificable($pedido, $usuario);

        return DB::transaction(function () use ($pedido, $nuevoEstado, $usuario, $observaciones) {
            // Lock de fila: evita que dos peticiones simultáneas despachen el
            // mismo pedido y cobren la cotización dos veces.
            $pedido = Pedido::whereKey($pedido->getKey())->lockForUpdate()->firstOrFail();

            if ($pedido->estado === $nuevoEstado) {
                return $pedido;
            }

            $this->asegurarTransicionPermitida($pedido, $nuevoEstado, $usuario);

            $pedido->estado = $nuevoEstado;

            match ($nuevoEstado) {
                EstadoPedido::Pendiente => $this->revertirAPendiente($pedido),
                EstadoPedido::Despachado => $this->aplicarDespacho($pedido),
                EstadoPedido::Entregado => $this->aplicarEntrega($pedido),
            };

            if ($observaciones !== null) {
                $pedido->observaciones = $observaciones;
            }

            $pedido->save();

            return $pedido->refresh();
        });
    }

    /**
     * Un pedido entregado no se toca. El administrador sí puede corregirlo.
     */
    public function asegurarModificable(Pedido $pedido, User $usuario): void
    {
        if ($pedido->estaEntregado() && ! $usuario->esAdmin()) {
            throw new PedidoInmutableException(
                'El pedido ya fue entregado y no puede modificarse. Solo un administrador puede corregirlo.'
            );
        }
    }

    private function asegurarTransicionPermitida(Pedido $pedido, EstadoPedido $nuevo, User $usuario): void
    {
        // Retroceder el estado es una corrección de la información original.
        if ($nuevo->orden() < $pedido->estado->orden() && ! $usuario->esAdmin()) {
            throw new RuntimeException(sprintf(
                'No se puede regresar el pedido de "%s" a "%s": solo el administrador puede corregir el estado.',
                $pedido->estado->etiqueta(),
                $nuevo->etiqueta(),
            ));
        }
    }

    /**
     * Despacho: cotiza el envío contra la transportadora y lo guarda.
     * Si la API falla, la excepción se propaga y la transacción no aplica,
     * así que el pedido sigue "pendiente" (sin costo de envío inventado).
     *
     * @throws CotizacionException
     */
    private function aplicarDespacho(Pedido $pedido): void
    {
        if ($pedido->costo_envio === null) {
            $cotizacion = $this->transportadora->cotizar(
                ciudadDestino: $pedido->cliente->ciudad,
                pesoKg: $this->pesoEstimado($pedido),
                subtotal: (float) $pedido->total,
            );

            $pedido->costo_envio = $cotizacion['costo_envio'];
        }

        $pedido->despachado_at = now();
        $pedido->entregado_at = null;
    }

    private function aplicarEntrega(Pedido $pedido): void
    {
        $pedido->entregado_at = now();
    }

    /**
     * Corrección del administrador hacia atrás: se limpia el rastro del
     * despacho para que no quede un costo de envío de un estado anterior.
     */
    private function revertirAPendiente(Pedido $pedido): void
    {
        $pedido->costo_envio = null;
        $pedido->despachado_at = null;
        $pedido->entregado_at = null;
    }

    /**
     * El peso lo define la empresa al despachar; acá lo derivamos del valor
     * del pedido para que la cotización tenga un dato real que enviar.
     */
    private function pesoEstimado(Pedido $pedido): float
    {
        return min(50, max(1, round((float) $pedido->total / 50_000, 1)));
    }
}
