# Storage permission contract

Laravel application files under `storage/app` may be created by HTTP requests, queue workers, scheduled jobs, Artisan commands, or container startup processes. Those processes do not always run as the same Unix user.

A file existing on disk is therefore **not sufficient**. Every parent directory must also be traversable by the PHP-FPM/web process. A typical failure is a root-owned private directory such as `drwx------ root root`: a root queue worker can create and verify the file, while PHP-FPM running as `www-data` sees the same path as unavailable and download endpoints return 404.

## Repository contract

`config/filesystems.php` defines the shared local-disk permission contract:

- private directories: `0770`
- private files: `0660`
- public directories: `0775`
- public files: `0664`

Docker additionally uses group `www-data` and the setgid bit on application storage directories (`2770`). Setgid makes new descendants inherit the shared web group even when a queue worker runs as another user such as `root`.

Do not add module-specific `0700` directories under `storage/app`. Do not rely only on `chmod` of the final file; all parent directories must be traversable by the web/queue group.

## Local development

When all Laravel processes run as the same local user, the filesystem config is normally enough. If PHP-FPM/nginx/Apache and CLI/queue run as different users, choose the web-server group and normalize `storage` once.

Example on Debian/Ubuntu where PHP-FPM uses `www-data`:

```bash
sudo chgrp -R www-data storage bootstrap/cache
sudo find storage/app -type d -exec chmod 2770 {} \;
sudo find storage/app -type f -exec chmod 0660 {} \;
sudo find storage/framework storage/logs bootstrap/cache -type d -exec chmod 2770 {} \;
sudo find storage/framework storage/logs bootstrap/cache -type f -exec chmod 0660 {} \;
```

If the developer needs direct write access too, add that user to the shared group rather than using `777`:

```bash
sudo usermod -aG www-data "$USER"
```

Log out/in after changing group membership.

## VPS without Docker

The web process and queue/scheduler must share a group. Prefer running workers as the same deployment/web user. If workers intentionally run as `root`, `storage/app` must still be owned/grouped so descendants inherit the web group.

After deployment or when recovering from permission drift:

```bash
cd /var/www/laravel-shop
sudo chgrp -R www-data storage bootstrap/cache
sudo find storage/app -type d -exec chmod 2770 {} \;
sudo find storage/app -type f -exec chmod 0660 {} \;
sudo find storage/framework storage/logs bootstrap/cache -type d -exec chmod 2770 {} \;
sudo find storage/framework storage/logs bootstrap/cache -type f -exec chmod 0660 {} \;
```

Then restart long-running queue workers so they load the current release/config:

```bash
php artisan queue:restart
```

Use the actual PHP-FPM group if the server does not use `www-data`.

## Docker / Compose

The application image and `docker/entrypoint.sh` normalize the complete `storage/app` tree at build/startup. This is important for persistent volumes because files from an older container keep their original ownership and mode.

The app container should start as root only long enough for the entrypoint to normalize ownership/permissions, then PHP-FPM workers run as configured (`www-data`). Queue containers using the same application image and persistent storage volume must mount the same `storage` volume and use the same group contract.

After changing the Docker permission contract, rebuild/recreate the application/queue containers; restarting only nginx is insufficient.

## Verification checklist

For a generated private file, verify both CLI and web access. On Linux:

```bash
namei -l /absolute/path/to/storage/app/example/file.xlsx
```

No parent below `storage/app` should unexpectedly be `drwx------ root root` when PHP-FPM runs as another user.

For Laravel-level verification:

```bash
php artisan tinker --execute='dump(config("filesystems.disks.local.root"), Storage::disk("local")->exists("relative/path/to/file"));'
```

If CLI says the file exists but an authenticated HTTP status/download endpoint says it does not, compare the process users (`ps`) and inspect every parent directory with `namei -l` before changing application logic.

## Security note

Do **not** solve storage errors with recursive `chmod 777`. Private application files may contain user uploads, exports, reports, backups, or other non-public data. The intended model is owner/group access (`0770` / `0660`) with a deliberately shared application group.
