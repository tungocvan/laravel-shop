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

    # storage/app is shared by HTTP requests, queue workers, scheduled jobs and
    # CLI commands. Keep every existing private directory group-traversable and
    # every file group-readable/writable so files never become invisible to
    # PHP-FPM when another process created them.
    find storage/app -type d -exec chmod 2770 {} \;
    find storage/app -type f -exec chmod 0660 {} \;
    find storage/framework storage/logs bootstrap/cache -type d -exec chmod 2770 {} \;
    find storage/framework storage/logs bootstrap/cache -type f -exec chmod 0660 {} \;

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
