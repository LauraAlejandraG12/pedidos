# syntax=docker/dockerfile:1
#
# Etapa 1: dependencias de Composer (se instalan una sola vez y se cachean por
# composer.lock). Se usa el contenedor de Composer como base para no instalar
# PHP en la imagen final.
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-scripts --no-autoloader --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-scripts --no-interaction

# Etapa 2: imagen base de PHP-FPM con las extensiones que Laravel necesita.
FROM php:8.4-fpm-alpine AS base

# Extensiones: pdo_mysql (MySQL), intl (fechas/locale), zip, bcmath (montos),
# opcache (rendimiento) y pdo_sqlite (para la suite de tests en memoria).
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev sqlite-dev pkgconf \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite intl zip bcmath opcache \
    && apk add --no-cache icu-libs libzip sqlite-libs \
    && apk del .build-deps

# Ajustes de PHP para desarrollo.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Etapa 3: entorno de desarrollo (vendor sembrado desde la etapa `vendor`,
# el código se monta por volumen en docker-compose).
FROM base AS dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor /var/www/html/vendor

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
