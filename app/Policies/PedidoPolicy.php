<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

/**
 * Regla central: los pedidos entregados son inmutables para todos menos
 * para el administrador.
 */
class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pedido $pedido): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Editar datos o cambiar el estado.
     */
    public function update(User $user, Pedido $pedido): bool
    {
        return $user->esAdmin() || ! $pedido->estaEntregado();
    }

    /**
     * Corregir un pedido entregado (solo administrador).
     */
    public function corregir(User $user, Pedido $pedido): bool
    {
        return $user->esAdmin();
    }

    public function delete(User $user, Pedido $pedido): bool
    {
        return false; // no se borran pedidos en este módulo
    }
}
