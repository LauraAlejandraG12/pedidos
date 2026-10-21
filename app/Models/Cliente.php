<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['nombre', 'ciudad'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Total vendido al cliente en un rango de fechas (reporte del mes).
     */
    public function totalVendidoEn(string $desde, string $hasta): float
    {
        return (float) $this->pedidos()
            ->whereBetween('fecha', [$desde, $hasta])
            ->sum('total');
    }
}
