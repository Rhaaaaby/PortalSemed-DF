#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric TCP port." >&2
        exit 1
        ;;
esac

sed "s/__PORT__/$port/g" /etc/nginx/http.d/default.conf > /tmp/render-nginx.conf
cp /tmp/render-nginx.conf /etc/nginx/http.d/default.conf
php-fpm -D
exec nginx -g 'daemon off;'
