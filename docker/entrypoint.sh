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
    find storage/app -mindepth 1 -type d -exec chmod 2770 {} \;
    find storage/app -type f -exec chmod 0660 {} \;

    # The public disk is served by the nginx container through public/storage.
    # Keep private application storage group-only, but allow nginx to traverse
    # public directories and read public files from the shared storage volume.
    chmod 2771 storage/app
    mkdir -p storage/app/public
    find storage/app/public -type d -exec chmod 2775 {} \;
    find storage/app/public -type f -exec chmod 0664 {} \;

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
