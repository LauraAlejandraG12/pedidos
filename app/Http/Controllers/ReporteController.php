<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /**
     * Total vendido por cliente en el mes actual, de mayor a menor.
     */
    public function ventasDelMes()
    {
        return view('reportes.ventas', $this->datosReporte());
    }

    /**
     * Misma información en JSON: GET /api/reporte/ventas
     *
     * @return array{mes: string, desde: string, hasta: string, total_general: float, filas: array<int, array<string, mixed>>}
     */
    public function ventasDelMesApi(): array
    {
        return $this->datosReporte();
    }

    /**
     * @return array{mes: string, desde: string, hasta: string, total_general: float, filas: array<int, array<string, mixed>>}
     */
    private function datosReporte(): array
    {
        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();

        $clientes = Cliente::query()
            ->withSum(['pedidos as total_vendido' => fn ($q) => $q->whereBetween('fecha', [$desde, $hasta])], 'total')
            ->withCount(['pedidos as cantidad_pedidos' => fn ($q) => $q->whereBetween('fecha', [$desde, $hasta])])
            ->orderByDesc('total_vendido')
            ->get()
            ->filter(fn (Cliente $cliente) => (float) ($cliente->total_vendido ?? 0) > 0)
            ->values();

        return [
            'mes' => now()->format('F Y'),
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'total_general' => round((float) $clientes->sum('total_vendido'), 2),
            'filas' => $clientes->map(fn (Cliente $cliente, int $i) => [
                'puesto' => $i + 1,
                'cliente_id' => $cliente->id,
                'cliente' => $cliente->nombre,
                'ciudad' => $cliente->ciudad,
                'pedidos' => (int) $cliente->cantidad_pedidos,
                'total_vendido' => round((float) $cliente->total_vendido, 2),
            ])->all(),
        ];
    }
}
