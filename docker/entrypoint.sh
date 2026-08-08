#!/usr/bin/env sh
set -eu

mkdir -p \
  storage/app \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ -f artisan ] && [ ! -L public/storage ]; then
    php artisan storage:link --quiet || true
fi

exec "$@"
