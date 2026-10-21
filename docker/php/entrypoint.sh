#!/bin/sh
# Arranque del contenedor de la aplicación Laravel.
set -e

cd /var/www/html

# 1. Esperar a que MySQL acepte conexiones (evita el "connection refused"
#    al levantar el stack la primera vez). Los datos de conexión se leen de
#    .env en vez de variables del entorno del SO, para no contaminar PHPUnit
#    (ver el comentario en docker-compose.yml).
if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    leer_env() {
        if [ -f .env ]; then
            grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
        fi
    }

    DB_HOST_VAL="${DB_HOST:-$(leer_env DB_HOST)}"
    DB_PORT_VAL="${DB_PORT:-$(leer_env DB_PORT)}"
    DB_NAME_VAL="${DB_DATABASE:-$(leer_env DB_DATABASE)}"
    DB_USER_VAL="${DB_USERNAME:-$(leer_env DB_USERNAME)}"
    DB_PASS_VAL="${DB_PASSWORD:-$(leer_env DB_PASSWORD)}"

    echo "Esperando a la base de datos en ${DB_HOST_VAL:-db}:${DB_PORT_VAL:-3306} ..."
    DB_HOST="$DB_HOST_VAL" DB_PORT="$DB_PORT_VAL" DB_NAME="$DB_NAME_VAL" DB_USER="$DB_USER_VAL" DB_PASS="$DB_PASS_VAL" php -r '
        $intentos = 0;
        while (true) {
            try {
                new PDO(
                    sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "db", getenv("DB_PORT") ?: "3306", getenv("DB_NAME")),
                    getenv("DB_USER"),
                    getenv("DB_PASS"),
                    [PDO::ATTR_TIMEOUT => 2]
                );
                fwrite(STDOUT, "Base de datos lista.\n");
                break;
            } catch (Throwable $e) {
                if (++$intentos > 60) {
                    fwrite(STDERR, "La base de datos no respondio en 60s: ".$e->getMessage()."\n");
                    exit(1);
                }
                usleep(500000);
            }
        }
    ' || exit 1
fi

# 2. Dependencias: el vendor viene sembrado desde la imagen, pero si faltan
#    (por ejemplo la primera vez en otra máquina) se instalan.
if [ ! -f vendor/autoload.php ]; then
    echo "Instalando dependencias de Composer ..."
    composer install --no-interaction --no-progress
fi

# 3. Permisos de los directorios que escribe PHP-FPM (www-data).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# 4. Migrar (y opcionalmente sembrar) al arrancar.
if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction

    if [ "${SEED_ON_START:-false}" = "true" ]; then
        php artisan db:seed --force --no-interaction
    fi
fi

exec "$@"
