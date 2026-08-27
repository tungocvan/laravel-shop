#!/usr/bin/env sh
set -eu

mkdir -p \
  storage/app \
  storage/app/system \
  storage/app/request/attachments \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod 2770 storage/app/system storage/app/request storage/app/request/attachments
    find storage/app/system -maxdepth 1 -type f -exec chmod 0660 {} \;

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
