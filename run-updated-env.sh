#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
ENV_FILE="$SCRIPT_DIR/.env"
cd "$SCRIPT_DIR"

fail() { printf '[FAIL] %s\n' "$*" >&2; exit 1; }
info() { printf '[INFO] %s\n' "$*"; }
pass() { printf '[PASS] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
compose() { docker compose -p "$PROJECT_NAME" "$@"; }
app_id() { compose ps -q app 2>/dev/null | head -1; }

command -v docker >/dev/null 2>&1 || fail 'Docker không tồn tại trong PATH.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose plugin không khả dụng.'
[[ -f "$ENV_FILE" ]] || fail "Không tìm thấy production .env: $ENV_FILE"

printf '=== PRODUCTION ENV APPLY ===\nProject       : %s\nPath          : %s\nCompose project: %s\nEnvironment   : %s\nImage rebuild : NO\nDatabase      : NO MIGRATION\n\n' "$PROJECT_NAME" "$SCRIPT_DIR" "$PROJECT_NAME" "$ENV_FILE"

info 'Validate Docker Compose configuration...'
compose config --quiet || fail 'Compose config không hợp lệ. Container hiện tại chưa bị thay đổi.'
pass 'Compose config hợp lệ.'

APP="$(app_id)"
[[ -n "$APP" ]] || fail "Compose app service hiện không chạy cho project $PROJECT_NAME. Dừng để tránh reconcile production stack chưa rõ trạng thái."
[[ "$(docker inspect -f '{{.State.Running}}' "$APP" 2>/dev/null)" == true ]] || fail "Compose app service không running cho project: $PROJECT_NAME"

DEGRADED="$(compose ps --all --format '{{.Service}}|{{.State}}|{{.Status}}' 2>/dev/null | awk -F'|' '$2 != "running" {print}')"
if [[ -n "$DEGRADED" ]]; then
    printf '%s\n' "$DEGRADED" >&2
    fail "Production Compose project $PROJECT_NAME đang có service không running. Diagnosis/fix topology trước khi apply .env."
fi

info "Reconcile Compose project $PROJECT_NAME từ .env hiện tại, không build image..."
compose up -d --no-build || fail 'Docker Compose reconciliation thất bại.'

APP="$(app_id)"
[[ -n "$APP" && "$(docker inspect -f '{{.State.Running}}' "$APP" 2>/dev/null)" == true ]] || fail 'Compose app service không chạy sau reconciliation.'

info 'Refresh Laravel configuration cache...'
docker exec "$APP" bash -lc 'php artisan config:clear && php artisan config:cache' || fail 'Laravel config refresh thất bại.'
pass 'Laravel config cache đã refresh.'

info 'Yêu cầu Laravel queue workers reload application state...'
docker exec "$APP" bash -lc 'php artisan queue:restart' && pass 'queue:restart hoàn tất.' || warn 'queue:restart thất bại.'

if compose config --services | grep -qx scheduler; then
    info 'Restart scheduler...'
    compose restart scheduler >/dev/null && pass 'Scheduler đã restart.' || warn 'Không restart được scheduler.'
fi

printf '\n=== SERVICE STATUS ===\n'
compose ps --all
DEGRADED="$(compose ps --all --format '{{.Service}}|{{.State}}|{{.Status}}' 2>/dev/null | awk -F'|' '$2 != "running" {print}')"
printf '\n=== RESULT ===\n'
[[ -z "$DEGRADED" ]] || {
    printf '%s\n' "$DEGRADED" >&2
    fail 'Apply hoàn tất nhưng stack chưa running hoàn toàn.'
}
pass "Environment đã apply cho Compose project $PROJECT_NAME mà không rebuild image hoặc migrate database."
