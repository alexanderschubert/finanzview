FROM node:22-bookworm AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build



FROM php:8.4-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        bcmath \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html


COPY composer.json composer.lock ./


RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --no-dev \
    --optimize-autoloader


COPY . .


COPY --from=frontend /var/www/html/public/build ./public/build


COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-finanzblick.ini

COPY docker/entrypoint.sh /usr/local/bin/finanzblick-entrypoint


RUN chmod +x /usr/local/bin/finanzblick-entrypoint \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && mkdir -p storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache


EXPOSE 9000


ENTRYPOINT ["finanzblick-entrypoint"]

CMD ["php-fpm", "-F"]