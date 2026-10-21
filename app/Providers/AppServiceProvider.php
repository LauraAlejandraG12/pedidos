<?php

namespace App\Providers;

use App\Services\Transportadora\TransportadoraClient;
use App\Services\Transportadora\TransportadoraHttp;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola implementación del cliente de la transportadora para toda
        // la app. En los tests se sustituye con Http::fake() y en producción
        // solo cambia la URL del .env.
        $this->app->bind(TransportadoraClient::class, TransportadoraHttp::class);
    }

    public function boot(): void
    {
        // Paginación con el marcado propio del proyecto: el módulo no usa
        // Tailwind ni Bootstrap, así que evitamos su CSS.
        Paginator::defaultView('pagination::pedidos');
        Paginator::defaultSimpleView('pagination::pedidos');
    }
}
