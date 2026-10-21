<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Exceptions\PedidoInmutableException;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\GestorPedidos;
use App\Services\Transportadora\CotizacionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function __construct(private readonly GestorPedidos $gestor)
    {
    }

    /**
     * Listado de pedidos con filtro por estado.
     */
    public function index(Request $request)
    {
        $request->validate(
            ['estado' => ['nullable', Rule::in(EstadoPedido::valores())]],
            ['estado.in' => 'El estado debe ser pendiente, despachado o entregado.'],
        );

        $estado = $request->query('estado');

        $pedidos = Pedido::query()
            ->with('cliente:id,nombre,ciudad')
            ->estado($estado)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'estadoSeleccionado' => $estado,
            'estados' => EstadoPedido::cases(),
            'resumen' => $this->resumenPorEstado(),
        ]);
    }

    public function create()
    {
        return view('pedidos.create', [
            'clientes' => Cliente::orderBy('nombre')->get(),
        ]);
    }

    public function store(StorePedidoRequest $request)
    {
        $pedido = $this->gestor->crear($request->validated());

        return redirect()
            ->route('pedidos.index')
            ->with('exito', "Pedido #{$pedido->id} creado correctamente.");
    }

    public function edit(Pedido $pedido)
    {
        Gate::authorize('update', $pedido);

        return view('pedidos.edit', [
            'pedido' => $pedido->load('cliente:id,nombre,ciudad'),
            'clientes' => Cliente::orderBy('nombre')->get(),
        ]);
    }

    public function update(StorePedidoRequest $request, Pedido $pedido)
    {
        try {
            $this->gestor->actualizar($pedido, $request->validated(), $request->user());
        } catch (PedidoInmutableException $e) {
            return back()->withErrors(['pedido' => $e->getMessage()]);
        }

        return redirect()
            ->route('pedidos.index')
            ->with('exito', "Pedido #{$pedido->id} actualizado.");
    }

    /**
     * Cambia el estado del pedido (desde el listado o desde la edición).
     */
    public function cambiarEstado(CambiarEstadoRequest $request, Pedido $pedido)
    {
        try {
            $this->gestor->cambiarEstado(
                pedido: $pedido,
                nuevoEstado: EstadoPedido::from($request->validated('estado')),
                usuario: $request->user(),
                observaciones: $request->validated('observaciones'),
            );
        } catch (CotizacionException $e) {
            // La transportadora falló: el pedido queda como estaba.
            return back()->withErrors(['estado' => 'No se pudo despachar el pedido: '.$e->getMessage()]);
        } catch (PedidoInmutableException | \RuntimeException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return back()->with('exito', "Estado del pedido #{$pedido->id} actualizado.");
    }

    /**
     * Contadores por estado para las "chips" del listado.
     *
     * @return array<string, int>
     */
    private function resumenPorEstado(): array
    {
        return Pedido::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->all();
    }
}
