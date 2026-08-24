# =========================================================
# Stage 1: Frontend bauen
# =========================================================

FROM node:22-bookworm AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build


# =========================================================
# Stage 2: PHP + Nginx
# =========================================================

FROM php:8.4-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive


# ---------------------------------------------------------
# Pakete installieren
# ---------------------------------------------------------

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        supervisor \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        bcmath \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*


# ---------------------------------------------------------
# Composer
# ---------------------------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ---------------------------------------------------------
# Laravel
# ---------------------------------------------------------

WORKDIR /var/www/html


# Composer-Dateien zuerst kopieren
# Dadurch kann Docker den Composer-Layer cachen.

COPY composer.json composer.lock ./


# Composer-Abhängigkeiten installieren.
#
# --no-scripts:
# Laravel Artisan ist zu diesem Zeitpunkt noch nicht
# vorhanden. Deshalb werden Composer-Scripts zunächst
# deaktiviert.
#
# --prefer-source:
# Vermeidet Probleme mit GitHub-ZIP-Downloads.

RUN composer install \
    --no-interaction \
    --prefer-source \
    --no-progress \
    --no-scripts \
    --optimize-autoloader


# ---------------------------------------------------------
# Laravel-Projekt
# ---------------------------------------------------------

COPY . .


# ---------------------------------------------------------
# Composer Autoloader
# ---------------------------------------------------------

# Jetzt ist artisan vorhanden.
# Deshalb können die Laravel Composer-Scripts ausgeführt
# werden.

RUN composer dump-autoload --optimize


# ---------------------------------------------------------
# Frontend Assets
# ---------------------------------------------------------

COPY --from=frontend /var/www/html/public/build ./public/build


# ---------------------------------------------------------
# PHP Konfiguration
# ---------------------------------------------------------

COPY docker/php/php.ini \
    /usr/local/etc/php/conf.d/99-finanzblick.ini


# ---------------------------------------------------------
# Nginx Standard-Konfiguration entfernen
# ---------------------------------------------------------

# Debian installiert standardmäßig eine eigene Nginx-
# Konfiguration. Diese würde mit unserer Laravel-
# Konfiguration kollidieren und die Nginx-Willkommensseite
# anzeigen.

RUN rm -f \
        /etc/nginx/conf.d/default.conf \
        /etc/nginx/sites-enabled/default


# ---------------------------------------------------------
# Nginx Konfiguration
# ---------------------------------------------------------

COPY docker/nginx/default.conf \
    /etc/nginx/conf.d/finanzblick.conf


# ---------------------------------------------------------
# Supervisor
# ---------------------------------------------------------

COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf


# ---------------------------------------------------------
# Entrypoint
# ---------------------------------------------------------

COPY docker/entrypoint.sh \
    /usr/local/bin/finanzblick-entrypoint


# ---------------------------------------------------------
# Verzeichnisse und Berechtigungen
# ---------------------------------------------------------

RUN chmod +x /usr/local/bin/finanzblick-entrypoint \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache


# ---------------------------------------------------------
# Port
# ---------------------------------------------------------

EXPOSE 80


# ---------------------------------------------------------
# Start
# ---------------------------------------------------------

ENTRYPOINT ["finanzblick-entrypoint"]

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]