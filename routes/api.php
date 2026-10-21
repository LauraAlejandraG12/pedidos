<?php

use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del módulo de pedidos
|--------------------------------------------------------------------------
|
| GET  /api/pedidos?estado=pendiente        listado JSON (público)
| GET  /api/pedidos/{id}                    detalle (público)
| PATCH /api/pedidos/{id}/estado            cambio de estado (requiere token)
| GET  /api/reporte/ventas                  total vendido por cliente (público)
|
| Los endpoints de escritura requieren un token de Sanctum:
|   php artisan sanctum:create-token "mi-app"
|   curl -H "Authorization: Bearer {token}" -X PATCH ...
|
*/

Route::get('/pedidos', [PedidoController::class, 'index']);
Route::get('/pedidos/{pedido}', [PedidoController::class, 'show']);
Route::get('/reporte/ventas', [ReporteController::class, 'ventasDelMesApi']);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado']);
});
