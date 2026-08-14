#!/usr/bin/env sh
set -eu

mkdir -p \
  storage/app \
  storage/app/system \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache

    if [ -d Modules ]; then
        chown -R www-data:www-data Modules
        find Modules -type d -exec chmod ug+rwx {} \;
        find Modules -type f -exec chmod ug+rw {} \;
    fi
fi

if [ -f artisan ] && [ ! -L public/storage ]; then
    php artisan storage:link --quiet || true
fi

exec "$@"
