<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Genera un token de API para poder probar los endpoints protegidos:
 *
 *   docker compose exec app php artisan pedidos:token admin@uva3.test
 */
class CrearTokenApi extends Command
{
    protected $signature = 'pedidos:token
                            {email? : Correo del usuario dueño del token}
                            {--nombre=cli : Nombre identificador del token}';

    protected $description = 'Crea un token de Sanctum para usar la API de pedidos';

    public function handle(): int
    {
        $email = $this->argument('email') ?? 'admin@uva3.test';

        $usuario = User::where('email', $email)->first();

        if (! $usuario) {
            $this->error("No existe ningún usuario con el correo {$email}.");

            return self::FAILURE;
        }

        $token = $usuario->createToken($this->option('nombre'))->plainTextToken;

        $this->info('Token generado:');
        $this->line("  Bearer {$token}");
        $this->newLine();
        $this->line('Ejemplo de uso:');
        $this->line('  curl -X PATCH "http://localhost:8000/api/pedidos/1/estado" \\');
        $this->line('    -H "Authorization: Bearer '.$token.'" \\');
        $this->line('    -H "Content-Type: application/json" \\');
        $this->line('    -d \'{"estado": "despachado"}\'');

        return self::SUCCESS;
    }
}
