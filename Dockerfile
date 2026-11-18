# syntax=docker/dockerfile:1

# =========================================================
# FinanzView – Docker Image
# =========================================================
#
# Stufen:
#   frontend  CSS/JS mit Vite bauen
#   base      PHP-FPM + Nginx + Supervisor auf Alpine
#   vendor    PHP-Pakete ohne Entwicklungswerkzeuge
#   test      Image für die Tests in der CI (mit PHPUnit)
#   app       Fertiges Image für den Betrieb (Standard)
#
# Bauen:  docker build -t finanzview .
# Tests:  docker build --target test -t finanzview-test .


# =========================================================
# Frontend bauen
# =========================================================

FROM node:24-alpine AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build


# =========================================================
# Basis: PHP + Nginx + Supervisor
# =========================================================

FROM php:8.5-fpm-alpine AS base

# Laufzeitbibliotheken bleiben, Build-Werkzeuge werden nach dem
# Kompilieren der PHP-Erweiterungen wieder entfernt. OPcache ist seit
# PHP 8.5 fester Bestandteil von PHP und muss nicht mehr gebaut werden.
RUN apk add --no-cache \
        nginx \
        supervisor \
        tzdata \
        libpq \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        bcmath \
        zip \
    && apk del .build-deps \
    && rm -rf /tmp/* /usr/src/php*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# =========================================================
# PHP-Pakete für den Betrieb
# =========================================================
#
# --prefer-dist lädt fertige Pakete statt kompletter
# Git-Repositories (das hat das Image früher auf ~7 GB
# aufgebläht). --no-dev lässt PHPUnit & Co. weg.

FROM base AS vendor

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --no-dev \
        --prefer-dist \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress


# =========================================================
# Test-Image (nur für die CI)
# =========================================================

FROM base AS test

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --prefer-dist \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress

COPY . .
COPY --from=frontend /var/www/html/public/build ./public/build

RUN composer dump-autoload --optimize --no-scripts


# =========================================================
# Fertiges Image
# =========================================================

FROM base AS app

# Eingebaute PostgreSQL-Datenbank: wird nur gestartet, wenn kein DB_HOST gesetzt ist
# (siehe docker/entrypoint.sh). Die Hauptversion ist fest (18); der Datenordner liegt
# in einem Unterordner pro Hauptversion (pg18).
RUN apk add --no-cache \
        postgresql18 \
        postgresql18-client \
        su-exec \
    && mkdir -p /run/postgresql /var/lib/postgresql/data /etc/supervisor/conf.d \
    && chown postgres:postgres /run/postgresql

# PostgreSQL ist der einzige unterstützte Treiber; so findet auch ein
# "docker exec ... php artisan" die eingebaute Datenbank.
ENV DB_CONNECTION=pgsql

COPY --from=vendor /var/www/html/vendor ./vendor

COPY . .
COPY --from=frontend /var/www/html/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && rm -rf tests


# ---------------------------------------------------------
# Konfiguration
# ---------------------------------------------------------

COPY docker/php/php.ini \
    /usr/local/etc/php/conf.d/99-finanzview.ini

# Alpine liefert eine eigene Nginx-Standardseite mit.
RUN rm -f /etc/nginx/http.d/default.conf

COPY docker/nginx/default.conf \
    /etc/nginx/http.d/finanzview.conf

COPY docker/supervisord.conf \
    /etc/supervisor/finanzview.conf

COPY docker/entrypoint.sh \
    /usr/local/bin/finanzview-entrypoint


# ---------------------------------------------------------
# Verzeichnisse und Berechtigungen
# ---------------------------------------------------------

RUN chmod +x /usr/local/bin/finanzview-entrypoint \
    && mkdir -p /run/nginx \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache


EXPOSE 80

ENTRYPOINT ["finanzview-entrypoint"]

CMD ["supervisord", "-c", "/etc/supervisor/finanzview.conf"]
