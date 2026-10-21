<?php

namespace App\Services\Transportadora;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente real de la API de tarifas (POST {TARIFAS_BASE_URL}/v1/cotizar).
 *
 * La URL y la credencial salen del .env:
 *   TARIFAS_BASE_URL   ej. https://tarifas.transportadora.test
 *   TARIFAS_API_KEY    credencial que la transportadora entrega por solicitud
 *
 * En este proyecto TARIFAS_BASE_URL apunta al stub local del compose
 * (servicio `tarifas`), porque el dominio del enunciado no existe.
 */
class TransportadoraHttp implements TransportadoraClient
{
    public function cotizar(string $ciudadDestino, float $pesoKg, float $subtotal): array
    {
        $baseUrl = rtrim((string) config('services.transportadora.base_url'), '/');
        $clave = (string) config('services.transportadora.key');

        if ($baseUrl === '') {
            throw new CotizacionException('No está configurada la URL de la API de tarifas (TARIFAS_BASE_URL).');
        }

        if ($clave === '') {
            throw new CotizacionException('No está configurada la credencial de la transportadora (TARIFAS_API_KEY).');
        }

        try {
            $respuesta = Http::withToken($clave)
                ->acceptJson()
                ->timeout((int) config('services.transportadora.timeout', 5))
                ->retry(
                    times: (int) config('services.transportadora.reintentos', 2),
                    sleepMilliseconds: 200,
                    throw: false,
                    when: fn (Throwable $e) => ! $e instanceof CotizacionException,
                )
                ->post($baseUrl.'/v1/cotizar', [
                    'destino' => $ciudadDestino,
                    'peso_kg' => $pesoKg,
                    'subtotal' => $subtotal,
                ]);
        } catch (ConnectionException $e) {
            throw new CotizacionException('No se pudo contactar a la transportadora: '.$e->getMessage(), previous: $e);
        }

        if ($respuesta->failed()) {
            $mensaje = $respuesta->json('mensaje') ?? $respuesta->json('error') ?? 'HTTP '.$respuesta->status();
            throw new CotizacionException('La transportadora rechazó la cotización: '.$mensaje);
        }

        $costo = $respuesta->json('costo_envio');

        if ($costo === null || ! is_numeric($costo)) {
            throw new CotizacionException('La transportadora respondió sin el campo costo_envio.');
        }

        return [
            'costo_envio' => (float) $costo,
            'moneda' => $respuesta->json('moneda') ?? 'COP',
            'eta_dias' => (int) ($respuesta->json('eta_dias') ?? 0),
            'cotizacion_id' => (string) ($respuesta->json('cotizacion_id') ?? ''),
        ];
    }
}
