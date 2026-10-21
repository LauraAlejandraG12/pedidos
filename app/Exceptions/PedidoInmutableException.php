<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Un pedido entregado no se puede modificar (excepto por el administrador).
 * El controlador la convierte en un 422 con mensaje claro.
 */
class PedidoInmutableException extends RuntimeException
{
}
