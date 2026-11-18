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
#   app       Fertiges Image für den Betrieb (Datenbank separat)
#   aio       All-in-One: wie app, aber mit PostgreSQL im selben Container
#
# Bauen:  docker build --target app -t finanzview .
# AIO:    docker build --target aio -t finanzview-aio .
# Tests:  docker build --target test -t finanzview-tests .


# =========================================================
# Frontend bauen
# =========================================================

FROM node:22-alpine AS frontend

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

FROM php:8.4-fpm-alpine AS base

# Laufzeitbibliotheken bleiben, Build-Werkzeuge werden nach dem
# Kompilieren der PHP-Erweiterungen wieder entfernt.
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
        opcache \
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
    /etc/supervisor/conf.d/supervisord.conf

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

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


# =========================================================
# All-in-One: App + PostgreSQL + tägliche Sicherung
# =========================================================
#
# Alle Daten liegen im Volume /data (Datenbank, Storage, Sicherungen).
# PostgreSQL ist auf Hauptversion 17 festgelegt: bei einem Wechsel der
# Hauptversion lässt sich ein bestehendes Datenverzeichnis nicht mehr
# starten, deshalb wird die Version nicht mitgezogen.

FROM app AS aio

RUN apk add --no-cache \
        postgresql17 \
        postgresql17-client \
        su-exec \
        tini

COPY docker/aio/entrypoint.sh /usr/local/bin/finanzview-aio-entrypoint
COPY docker/aio/backup.sh /usr/local/bin/finanzview-backup
COPY docker/aio/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x /usr/local/bin/finanzview-aio-entrypoint /usr/local/bin/finanzview-backup \
    && mkdir -p /data /run/postgresql \
    && chown postgres:postgres /run/postgresql

ENV DB_CONNECTION=pgsql \
    DB_HOST=127.0.0.1 \
    DB_PORT=5432 \
    DB_DATABASE=finanzview \
    DB_USERNAME=finanzview

VOLUME /data

HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["/sbin/tini", "--", "finanzview-aio-entrypoint"]

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
