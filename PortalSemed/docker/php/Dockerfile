FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql

WORKDIR /var/www/portalsemed