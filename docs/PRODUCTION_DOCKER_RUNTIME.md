# Production Docker Runtime Contract

Production projects live at `/opt/projects/<project-name>`, but Docker Compose is the source of truth for runtime container identity. Never infer the app container as `<directory>-app-1`; `COMPOSE_PROJECT_NAME` or Compose `name:` may differ. Resolve it with `docker compose ps -q app`. The app working directory is `/var/www/html`.

The canonical runtime environment is `/opt/projects/<project>/.env`. Compose mounts it into PHP services and consumes values through interpolation; socket uses `env_file: .env`. `.env` is excluded by `.dockerignore`, is not an image artifact, and must never be committed or dumped in diagnostics. Laravel-only changes normally require config-cache refresh. Compose interpolation, `environment:` or `env_file:` changes can require service reconciliation/recreation. MariaDB initialization variables need separate planning with an existing named DB volume.

Use `./run-docker-artisan.sh "php artisan <command>"`; it resolves the Compose `app` service. Use `./production-debug.sh` for read-only diagnosis, with optional `--docker`, `--permissions`, `--database`, or `--logs`. PHP runtime writability is checked as `www-data`, not merely root. A degraded stack (`restarting`, `created`, `exited`, `dead`, or otherwise non-running) is a diagnosis signal and must not be automatically reconciled before its root cause/topology is understood.

After an operator changes production `.env`, use `./run-updated-env.sh`. It validates Compose, resolves `app` from Compose, refuses automatic apply when the current stack already has non-running services, reconciles with `docker compose up -d --no-build`, refreshes Laravel config, signals queue workers, restarts scheduler when present, and reports final service states. It performs no migration and no database mutation.

The entrypoint prepares runtime directories for PHP-FPM `www-data`. Do not use `chmod 777`; distinguish root/CLI writability from `www-data` writability.

Production diagnosis is read-only by default. Do not use `migrate:fresh`, `db:wipe`, rollback, `git reset --hard`, `git clean -fd`, volume deletion, permission mutation, or `.env` edits as diagnosis defaults. Production-only Compose overlays may intentionally be untracked; inspect them and never remove them without explicit operator approval.
