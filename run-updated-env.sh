#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
ENV_FILE="$SCRIPT_DIR/.env"
APP_CONTAINER="${PROJECT_NAME}-app-1"

fail() { printf '[FAIL] %s\n' "$*" >&2; exit 1; }
info() { printf '[INFO] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
pass() { printf '[PASS] %s\n' "$*"; }

command -v docker >/dev/null 2>&1 || fail 'Docker không tồn tại trong PATH.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose plugin không khả dụng.'
[[ -f compose.yaml || -f compose.yml || -f docker-compose.yml || -f docker-compose.yaml ]] || fail 'Không tìm thấy Compose file tại project root.'
[[ -f "$ENV_FILE" ]] || fail "Không tìm thấy production .env: $ENV_FILE"

printf '%s\n' '=== PRODUCTION ENV APPLY ==='
printf 'Project      : %s\n' "$PROJECT_NAME"
printf 'Path         : %s\n' "$SCRIPT_DIR"
printf 'Environment  : %s\n' "$ENV_FILE"
printf '%s\n' 'Image rebuild: NO'
printf '%s\n\n' 'Database     : NO MIGRATION'

info 'Validate Docker Compose configuration...'
docker compose config --quiet || fail 'Compose config không hợp lệ. Container hiện tại chưa bị thay đổi.'
pass 'Compose config hợp lệ.'

# These values affect image/build selection. This helper intentionally never builds.
BUILD_TIME_KEYS=(PHP_VERSION NODE_VERSION INSTALL_LIBREOFFICE MARIADB_VERSION REDIS_VERSION)
for key in "${BUILD_TIME_KEYS[@]}"; do
    if grep -Eq "^[[:space:]]*${key}=" "$ENV_FILE"; then
        warn "$key có thể ảnh hưởng image/build. run-updated-env.sh chỉ apply runtime và không rebuild image."
    fi
done

info 'Reconcile services từ .env hiện tại, không build image...'
docker compose up -d --no-build || fail 'Docker Compose reconciliation thất bại.'

if ! docker inspect "$APP_CONTAINER" >/dev/null 2>&1; then
    fail "Không tìm thấy application container: $APP_CONTAINER"
fi
if [[ "$(docker inspect -f '{{.State.Running}}' "$APP_CONTAINER")" != 'true' ]]; then
    fail "Application container không chạy: $APP_CONTAINER"
fi

info 'Refresh Laravel configuration cache...'
docker exec "$APP_CONTAINER" bash -lc 'php artisan config:clear && php artisan config:cache' || fail 'Laravel config refresh thất bại.'
pass 'Laravel config cache đã được refresh.'

info 'Yêu cầu Laravel queue workers reload application state...'
if docker exec "$APP_CONTAINER" bash -lc 'php artisan queue:restart'; then
    pass 'queue:restart hoàn tất.'
else
    warn 'queue:restart thất bại; kiểm tra queue/cache connection.'
fi

if docker compose config --services | grep -qx 'scheduler'; then
    info 'Restart scheduler để schedule:work nhận environment mới...'
    if docker compose restart scheduler >/dev/null; then
        pass 'Scheduler đã restart.'
    else
        warn 'Không restart được scheduler.'
    fi
fi

printf '\n%s\n' '=== SERVICE STATUS ==='
docker compose ps

unhealthy="$(docker compose ps --format json 2>/dev/null | grep -c '"Health":"unhealthy"' || true)"
exited="$(docker compose ps --all --format json 2>/dev/null | grep -Ec '"State":"(exited|dead)"' || true)"

printf '\n%s\n' '=== RESULT ==='
if [[ "$unhealthy" -gt 0 || "$exited" -gt 0 ]]; then
    warn "Apply hoàn tất nhưng phát hiện unhealthy=$unhealthy, exited/dead=$exited."
    exit 2
fi
pass 'Environment đã được apply mà không rebuild image hoặc migrate database.'
