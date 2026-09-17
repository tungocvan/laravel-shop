#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
APP_CONTAINER="${PROJECT_NAME}-app-1"
MODE="${1:-all}"

case "$MODE" in
    all|--docker|--permissions|--database|--logs) ;;
    *) echo "Usage: $0 [--docker|--permissions|--database|--logs]" >&2; exit 1 ;;
esac

section() { printf '\n=== %s ===\n' "$1"; }
has_app() { docker inspect "$APP_CONTAINER" >/dev/null 2>&1 && [[ "$(docker inspect -f '{{.State.Running}}' "$APP_CONTAINER" 2>/dev/null)" == 'true' ]]; }
run_app() { has_app && docker exec "$APP_CONTAINER" bash -lc "$1" 2>&1 || true; }

printf '%s\n' 'READ-ONLY DIAGNOSTIC'
printf '%s\n' 'No database mutation | No Git mutation | No permission mutation | No container recreation'
printf '%s\n' 'Secrets are not printed'

if [[ "$MODE" == all ]]; then
    section PROJECT
    printf 'path      : %s\nproject   : %s\ntimestamp : %s\nhostname  : %s\n' "$SCRIPT_DIR" "$PROJECT_NAME" "$(date -Is)" "$(hostname)"

    section GIT
    git branch --show-current 2>/dev/null || true
    git rev-parse --short HEAD 2>/dev/null || true
    git status --short --branch 2>/dev/null || true
    if git rev-parse --verify origin/main >/dev/null 2>&1; then
        printf 'ahead/behind origin/main: '
        git rev-list --left-right --count origin/main...HEAD 2>/dev/null || true
    fi

    section ENVIRONMENT
    if [[ -f .env ]]; then
        printf '.env host       : EXISTS\n'
        printf '.env permission : %s\n' "$(stat -c '%a %U:%G' .env 2>/dev/null || stat -f '%Lp %Su:%Sg' .env 2>/dev/null || echo unknown)"
    else
        printf '.env host       : MISSING\n'
    fi
    if has_app; then
        run_app 'test -f /var/www/html/.env && echo ".env container  : EXISTS" || echo ".env container  : MISSING"'
    fi
fi

if [[ "$MODE" == all || "$MODE" == --docker ]]; then
    section DOCKER
    docker --version 2>/dev/null || true
    docker compose version 2>/dev/null || true
    printf '\nServices:\n'
    docker compose config --services 2>/dev/null || true
    printf '\nContainers:\n'
    docker compose ps --all 2>/dev/null || true
    if has_app; then
        section APPLICATION_CONTAINER
        docker inspect -f 'name={{.Name}} running={{.State.Running}} health={{if .State.Health}}{{.State.Health.Status}}{{else}}n/a{{end}} user={{.Config.User}}' "$APP_CONTAINER" 2>/dev/null || true
        run_app 'printf "uid/gid: "; id; printf "cwd: "; pwd; php -v | head -1; php artisan --version; php artisan about --only=environment 2>/dev/null || true'
    fi
fi

if [[ "$MODE" == all || "$MODE" == --permissions ]]; then
    section FILESYSTEM
    if has_app; then
        run_app 'for p in storage storage/app storage/framework storage/logs bootstrap/cache; do if [ -e "$p" ]; then stat -c "%a %U:%G %n" "$p" 2>/dev/null || ls -ld "$p"; [ -w "$p" ] && echo "writable: $p YES" || echo "writable: $p NO"; else echo "missing: $p"; fi; done'
    else
        echo "Application container unavailable: $APP_CONTAINER"
    fi
fi

if [[ "$MODE" == all || "$MODE" == --database ]]; then
    section DATABASE
    if has_app; then
        run_app 'php artisan migrate:status --no-ansi 2>&1 || true'
    else
        echo "Application container unavailable: $APP_CONTAINER"
    fi
fi

if [[ "$MODE" == --logs ]]; then
    section LARAVEL_LOG
    run_app 'f=$(ls -1t storage/logs/*.log 2>/dev/null | head -1); if [ -n "$f" ]; then echo "file: $f"; tail -n 120 "$f"; else echo "No Laravel log found"; fi'
    section CONTAINER_LOG
    docker logs --tail 120 "$APP_CONTAINER" 2>&1 || true
fi

if [[ "$MODE" == all ]]; then
    section DISK
    df -h "$SCRIPT_DIR" 2>/dev/null || true
    docker system df 2>/dev/null || true

    section DIAGNOSIS_FLAGS
    dirty="$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')"
    [[ "$dirty" == '0' ]] && echo 'git_dirty: NO' || echo "git_dirty: YES ($dirty entries)"
    [[ -f .env ]] && echo '.env_host: OK' || echo '.env_host: MISSING'
    if has_app; then
        health="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}n/a{{end}}' "$APP_CONTAINER" 2>/dev/null || echo unknown)"
        echo "app_health: $health"
        run_app 'for p in storage/app bootstrap/cache; do [ -w "$p" ] && echo "$p writable: YES" || echo "$p writable: NO"; done'
    else
        echo 'app_container: NOT RUNNING'
    fi
fi
