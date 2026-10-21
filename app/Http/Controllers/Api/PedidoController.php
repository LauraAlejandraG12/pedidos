<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoPedido;
use App\Exceptions\PedidoInmutableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\GestorPedidos;
use App\Services\Transportadora\CotizacionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function __construct(private readonly GestorPedidos $gestor)
    {
    }

    /**
     * GET /api/pedidos?estado=pendiente
     *
     * Filtro opcional por estado; responde paginado en JSON.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        if ($request->filled('estado') && ! in_array($request->query('estado'), EstadoPedido::valores(), true)) {
            return response()->json([
                'message' => 'El estado debe ser pendiente, despachado o entregado.',
                'errors' => ['estado' => [sprintf('El estado "%s" no es válido.', $request->query('estado'))]],
            ], 422);
        }

        $pedidos = Pedido::query()
            ->with('cliente:id,nombre,ciudad')
            ->estado($request->query('estado'))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return PedidoResource::collection($pedidos);
    }

    /**
     * GET /api/pedidos/{id}
     */
    public function show(Pedido $pedido): PedidoResource
    {
        return new PedidoResource($pedido->load('cliente:id,nombre,ciudad'));
    }

    /**
     * PATCH /api/pedidos/{id}/estado  {"estado": "despachado"}
     *
     * Aplica las mismas reglas que la interfaz web: los entregados no se
     * tocan (422) y al despachar se cotiza el envío.
     */
    public function cambiarEstado(CambiarEstadoRequest $request, Pedido $pedido): PedidoResource|JsonResponse
    {
        try {
            // El gestor reconsulta el pedido dentro de la transacción
            // (lockForUpdate), así que hay que devolver SU instancia y no la
            // del binding de la ruta, que quedaría desactualizada.
            $pedido = $this->gestor->cambiarEstado(
                pedido: $pedido,
                nuevoEstado: EstadoPedido::from($request->validated('estado')),
                usuario: $request->user(),
                observaciones: $request->validated('observaciones'),
            );
        } catch (CotizacionException $e) {
            return response()->json([
                'message' => 'No se pudo despachar el pedido: la transportadora no respondió.',
                'detalle' => $e->getMessage(),
            ], 502);
        } catch (PedidoInmutableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return new PedidoResource($pedido->load('cliente:id,nombre,ciudad'));
    }
}
