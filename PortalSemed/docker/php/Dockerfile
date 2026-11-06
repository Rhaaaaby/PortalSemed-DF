FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql

RUN printf 'upload_max_filesize=5M\npost_max_size=10M\n' > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/portalsemed