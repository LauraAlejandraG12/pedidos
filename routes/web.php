<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pedidos');

// Sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Módulo de pedidos
Route::middleware('auth')->group(function () {
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');

    Route::get('/pedidos/crear', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');

    Route::get('/pedidos/{pedido}/editar', [PedidoController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('pedidos.update');

    Route::post('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('pedidos.estado');

    Route::get('/reportes/ventas', [ReporteController::class, 'ventasDelMes'])->name('reportes.ventas');
});
