#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
ENV_FILE="$SCRIPT_DIR/.env"
STATE_DIR="$SCRIPT_DIR/storage/app/platform-runtime"
BASELINE_FILE="$STATE_DIR/run-updated-env.last.env"
cd "$SCRIPT_DIR"

fail() { printf '[FAIL] %s\n' "$*" >&2; exit 1; }
info() { printf '[INFO] %s\n' "$*"; }
pass() { printf '[PASS] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
compose() { docker compose -p "$PROJECT_NAME" "$@"; }
app_id() { compose ps -q app 2>/dev/null | head -1; }

env_keys() { sed -n -E 's/^([A-Za-z_][A-Za-z0-9_]*)=.*/\1/p' "$1" 2>/dev/null | sort -u; }
env_value() { awk -v key="$2" 'index($0,key "=")==1 {print substr($0,length(key)+2); found=1; exit} END {if (!found) exit 1}' "$1"; }
env_diff_keys() {
    local old="$1" new="$2" key old_value new_value
    while IFS= read -r key; do
        [[ -n "$key" ]] || continue
        old_value="$(env_value "$old" "$key" 2>/dev/null || true)"
        new_value="$(env_value "$new" "$key" 2>/dev/null || true)"
        if ! grep -qE "^${key}=" "$old" 2>/dev/null || ! grep -qE "^${key}=" "$new" 2>/dev/null || [[ "$old_value" != "$new_value" ]]; then
            printf '%s\n' "$key"
        fi
    done < <(cat <(env_keys "$old") <(env_keys "$new") | sort -u)
}

compose_interpolation_keys() {
    local file
    for file in compose.yaml compose.yml docker-compose.yaml docker-compose.yml; do
        [[ -f "$SCRIPT_DIR/$file" ]] || continue
        grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*([^}]*)?\}' "$SCRIPT_DIR/$file" 2>/dev/null \
            | sed -E 's/^\$\{//; s/(:-|:-|:\?|\?|\+|:\+|-|\+).*//; s/\}$//' || true
    done | sort -u
}

usage() {
    cat <<'EOF'
USAGE:
  ./run-updated-env.sh
  ./run-updated-env.sh --smart
  ./run-updated-env.sh --smart --from <previous-env-file>
  ./run-updated-env.sh --snapshot

Modes:
  default     Legacy behavior: always reconcile Compose, no build/migration.
  --smart     Compare key-only changes with a previous .env snapshot. Compose is
              reconciled only when a changed key is actually interpolated by
              Compose or is an explicit runtime endpoint key (APP_URL,
              HTTP_PORT, SOCKET_PORT, COMPOSE_*). Otherwise only Laravel config,
              queue restart and health/status checks are performed.
  --from      Use an explicit previous .env as the comparison baseline.
  --snapshot  Save the current .env as the smart baseline; makes no runtime change.

Values/secrets are never printed by smart diff output.
EOF
}

MODE="legacy"
FROM_FILE=""
while [[ $# -gt 0 ]]; do
    case "$1" in
        --smart) MODE="smart"; shift ;;
        --from) [[ $# -ge 2 ]] || fail '--from cần đường dẫn previous .env'; FROM_FILE="$2"; shift 2 ;;
        --snapshot) MODE="snapshot"; shift ;;
        -h|--help) usage; exit 0 ;;
        *) fail "Option không hợp lệ: $1" ;;
    esac
done

command -v docker >/dev/null 2>&1 || fail 'Docker không tồn tại trong PATH.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose plugin không khả dụng.'
[[ -f "$ENV_FILE" ]] || fail "Không tìm thấy production .env: $ENV_FILE"

if [[ "$MODE" == snapshot ]]; then
    mkdir -p "$STATE_DIR"
    umask 077
    cp -p "$ENV_FILE" "$BASELINE_FILE"
    chmod 600 "$BASELINE_FILE" 2>/dev/null || true
    pass "Đã lưu smart baseline: $BASELINE_FILE"
    exit 0
fi

RECONCILE=1
CHANGED_KEYS=""
BASELINE=""
if [[ "$MODE" == smart ]]; then
    if [[ -n "$FROM_FILE" ]]; then
        [[ -f "$FROM_FILE" ]] || fail "Previous .env không tồn tại: $FROM_FILE"
        BASELINE="$FROM_FILE"
    elif [[ -f "$BASELINE_FILE" ]]; then
        BASELINE="$BASELINE_FILE"
    else
        warn "Chưa có smart baseline. Không thể xác minh key nào vừa thay đổi."
        warn "Safety fallback: giữ legacy Compose reconcile cho lần này."
        warn "Sau khi apply thành công, baseline sẽ được tạo tự động cho lần --smart tiếp theo."
    fi

    if [[ -n "$BASELINE" ]]; then
        CHANGED_KEYS="$(env_diff_keys "$BASELINE" "$ENV_FILE")"
        COMPOSE_KEYS="$(compose_interpolation_keys)"
        RECONCILE=0
        while IFS= read -r key; do
            [[ -n "$key" ]] || continue
            if grep -Fxq "$key" <<<"$COMPOSE_KEYS" || [[ "$key" == APP_URL || "$key" == HTTP_PORT || "$key" == SOCKET_PORT || "$key" == COMPOSE_* ]]; then
                RECONCILE=1
                break
            fi
        done <<<"$CHANGED_KEYS"

        printf 'Changed/added/removed keys (values redacted):\n'
        if [[ -n "$CHANGED_KEYS" ]]; then printf '%s\n' "$CHANGED_KEYS" | sed 's/^/  - /'; else printf '  (none)\n'; fi
        printf 'Compose reconcile required: %s\n\n' "$RECONCILE"
    fi
fi

printf '=== PRODUCTION ENV APPLY ===\nProject       : %s\nPath          : %s\nCompose project: %s\nEnvironment   : %s\nMode          : %s\nImage rebuild : NO\nDatabase      : NO MIGRATION\n\n' "$PROJECT_NAME" "$SCRIPT_DIR" "$PROJECT_NAME" "$ENV_FILE" "$MODE"

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

if [[ "$RECONCILE" -eq 1 ]]; then
    info "Reconcile Compose project $PROJECT_NAME từ .env hiện tại, không build image..."
    compose up -d --no-build || fail 'Docker Compose reconciliation thất bại.'
else
    info 'Smart mode: thay đổi không ảnh hưởng Compose/runtime endpoint; bỏ qua Compose reconcile.'
fi

APP="$(app_id)"
[[ -n "$APP" && "$(docker inspect -f '{{.State.Running}}' "$APP" 2>/dev/null)" == true ]] || fail 'Compose app service không chạy sau environment apply.'

info 'Refresh Laravel configuration cache...'
docker exec "$APP" bash -lc 'php artisan config:clear && php artisan config:cache' || fail 'Laravel config refresh thất bại.'
pass 'Laravel config cache đã refresh.'

info 'Yêu cầu Laravel queue workers reload application state...'
docker exec "$APP" bash -lc 'php artisan queue:restart' && pass 'queue:restart hoàn tất.' || warn 'queue:restart thất bại.'

# Scheduler restart is only necessary when Compose/runtime-sensitive values changed.
if [[ "$RECONCILE" -eq 1 ]] && compose config --services | grep -qx scheduler; then
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

if [[ "$MODE" == smart ]]; then
    mkdir -p "$STATE_DIR"
    umask 077
    cp -p "$ENV_FILE" "$BASELINE_FILE"
    chmod 600 "$BASELINE_FILE" 2>/dev/null || true
    pass 'Smart baseline đã cập nhật sau khi environment apply thành công.'
fi

if [[ "$RECONCILE" -eq 1 ]]; then
    pass "Environment đã apply cho Compose project $PROJECT_NAME với safe Compose reconcile, không rebuild image hoặc migrate database."
else
    pass "Environment đã apply ở smart app-only mode; Compose không bị recreate/reconcile."
fi
