# syntax=docker/dockerfile:1.7
#
# PetPulse production images. Two build targets, both from this file:
#
#   app  PHP-FPM runtime used by every PHP service (app, reverb, queue, scheduler).
#   web  Caddy with a copy of public/ (incl. the Vite build) so it can serve static
#        files itself and hand .php requests to the app container over FastCGI.
#
# Baking public/ into the web image (instead of sharing a volume) guarantees that a
# new deploy always serves the assets that match the PHP code.
#
# The VITE_REVERB_* values end up inside the JavaScript bundle, so they are build
# arguments; compose.yaml fills them from .env.

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------- vendor
FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-interaction --no-progress --no-scripts --no-autoloader \
    --prefer-dist --ignore-platform-reqs

# ---------------------------------------------------------------- assets
FROM node:22-alpine AS assets
WORKDIR /build

ARG VITE_APP_NAME=PetPulse
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
# app.css lists these vendor views as a Tailwind @source.
COPY --from=vendor /build/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------------------------------------------------------------- app
FROM php:${PHP_VERSION}-fpm-alpine AS app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql bcmath intl opcache pcntl redis zip \
    && apk add --no-cache postgresql16-client tzdata

ENV TZ=America/Bogota
COPY deploy/php.ini /usr/local/etc/php/conf.d/zz-petpulse.ini
COPY deploy/php-fpm.conf /usr/local/etc/php-fpm.d/zz-petpulse.conf

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /build/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /build/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction \
    && chmod +x deploy/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);'

ENTRYPOINT ["deploy/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------- web
FROM caddy:2-alpine AS web
COPY deploy/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public
