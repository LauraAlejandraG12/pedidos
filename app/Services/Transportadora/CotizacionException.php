<?php

namespace App\Services\Transportadora;

use RuntimeException;

/**
 * Error de comunicación con la API de tarifas de la transportadora.
 *
 * Se propaga hacia arriba para que el controlador responda 502 y NO cambie
 * el estado del pedido: no tiene sentido despachar sin haber cotizado el envío.
 */
class CotizacionException extends RuntimeException
{
}
