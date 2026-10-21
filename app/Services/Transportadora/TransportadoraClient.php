<?php

namespace App\Services\Transportadora;

interface TransportadoraClient
{
    /**
     * Cotiza el envío de un pedido a la ciudad de destino del cliente.
     *
     * @param  string  $ciudadDestino  ciudad del cliente (tabla clientes)
     * @param  float  $pesoKg  peso estimado del paquete en kilogramos
     * @param  float  $subtotal  valor declarado del pedido (para el seguro)
     * @return array{costo_envio: float, moneda: string, eta_dias: int, cotizacion_id: string}
     *
     * @throws CotizacionException si la API falla o responde con error.
     */
    public function cotizar(string $ciudadDestino, float $pesoKg, float $subtotal): array;
}
