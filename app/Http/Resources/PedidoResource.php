<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Pedido
 */
class PedidoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente' => [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'ciudad' => $this->cliente->ciudad,
            ],
            'total' => (float) $this->total,
            'costo_envio' => $this->costo_envio === null ? null : (float) $this->costo_envio,
            'costo_total' => round($this->costo_total, 2),
            'fecha' => $this->fecha->toDateString(),
            'estado' => $this->estado->value,
            'despachado_at' => $this->despachado_at?->toIso8601String(),
            'entregado_at' => $this->entregado_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
