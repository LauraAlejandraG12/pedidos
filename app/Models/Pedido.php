<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'cliente_id',
    'total',
    'fecha',
    'estado',
    'costo_envio',
    'despachado_at',
    'entregado_at',
    'observaciones',
])]
class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'costo_envio' => 'decimal:2',
            'fecha' => 'date',
            'estado' => EstadoPedido::class,
            'despachado_at' => 'datetime',
            'entregado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Filtro por estado usado por el listado web y por GET /api/pedidos.
     */
    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        return $query->when($estado !== null && $estado !== '', fn (Builder $q) => $q->where('estado', $estado));
    }

    public function estaEntregado(): bool
    {
        return $this->estado === EstadoPedido::Entregado;
    }

    /**
     * Valor total del pedido incluyendo el envío (lo que realmente factura).
     */
    public function getCostoTotalAttribute(): float
    {
        return (float) $this->total + (float) ($this->costo_envio ?? 0);
    }
}
