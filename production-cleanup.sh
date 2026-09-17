#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
cd "$SCRIPT_DIR"

fail() { printf '[FAIL] %s\n' "$*" >&2; exit 1; }
info() { printf '[INFO] %s\n' "$*"; }
pass() { printf '[PASS] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
compose() { docker compose -p "$PROJECT_NAME" "$@"; }
app_id() { compose ps -q app 2>/dev/null | head -1; }

usage() {
    cat <<'EOF'
Usage:
  ./production-cleanup.sh --report   Read-only disk/log report (default)
  ./production-cleanup.sh --logs     Rotate oversized Laravel log for this project only
  ./production-cleanup.sh --docker   Remove dangling images/build cache only; never volumes
  ./production-cleanup.sh --all      --logs + --docker

Environment overrides:
  LARAVEL_LOG_MAX_MB=200   Rotate storage/logs/laravel.log when it exceeds this size
  LARAVEL_LOG_KEEP=5       Keep this many laravel.log.cleanup-* archives
EOF
}

MODE="${1:---report}"
case "$MODE" in
    --report|--logs|--docker|--all) ;;
    -h|--help) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
esac

command -v docker >/dev/null 2>&1 || fail 'Docker không tồn tại trong PATH.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose plugin không khả dụng.'

APP="$(app_id)"
[[ -n "$APP" ]] || fail "Không tìm thấy app container đang chạy cho Compose project $PROJECT_NAME."
[[ "$(docker inspect -f '{{.State.Running}}' "$APP" 2>/dev/null)" == true ]] || fail "App container không running cho project $PROJECT_NAME."

report() {
    printf '=== PRODUCTION CLEANUP REPORT ===\nProject: %s\nMode   : READ-ONLY\n\n' "$PROJECT_NAME"
    printf '%s\n' '--- Laravel logs ---'
    docker exec "$APP" sh -lc 'du -h -d 1 storage/logs 2>/dev/null || du -sh storage/logs 2>/dev/null || true; find storage/logs -maxdepth 1 -type f -exec du -h {} \; 2>/dev/null | sort -h | tail -20' || true
    printf '\n%s\n' '--- Filesystem ---'
    df -h "$SCRIPT_DIR" 2>/dev/null || true
    printf '\n%s\n' '--- Docker disk usage ---'
    docker system df || true
    printf '\n%s\n' '[INFO] Report only. Không có dữ liệu nào bị xóa.'
}

cleanup_logs() {
    local max_mb="${LARAVEL_LOG_MAX_MB:-200}"
    local keep="${LARAVEL_LOG_KEEP:-5}"
    [[ "$max_mb" =~ ^[0-9]+$ && "$max_mb" -gt 0 ]] || fail 'LARAVEL_LOG_MAX_MB phải là số nguyên > 0.'
    [[ "$keep" =~ ^[0-9]+$ && "$keep" -ge 0 ]] || fail 'LARAVEL_LOG_KEEP phải là số nguyên >= 0.'

    info "Kiểm tra storage/logs/laravel.log của project $PROJECT_NAME (ngưỡng ${max_mb} MB)..."
    docker exec -e MAX_MB="$max_mb" -e KEEP="$keep" "$APP" sh -lc '
        set -eu
        cd /var/www/html
        log="storage/logs/laravel.log"
        [ -f "$log" ] || { echo "[PASS] Không có laravel.log để rotate."; exit 0; }
        bytes=$(wc -c < "$log")
        limit=$((MAX_MB * 1024 * 1024))
        if [ "$bytes" -le "$limit" ]; then
            echo "[PASS] laravel.log chưa vượt ngưỡng; không thay đổi."
            exit 0
        fi
        stamp=$(date +%Y%m%d-%H%M%S)
        archive="storage/logs/laravel.log.cleanup-$stamp"
        mv "$log" "$archive"
        : > "$log"
        chown --reference="$archive" "$log" 2>/dev/null || true
        chmod --reference="$archive" "$log" 2>/dev/null || true
        if [ "$KEEP" -eq 0 ]; then
            rm -f storage/logs/laravel.log.cleanup-*
        else
            ls -1t storage/logs/laravel.log.cleanup-* 2>/dev/null | tail -n +$((KEEP + 1)) | xargs -r rm -f
        fi
        echo "[PASS] Đã rotate laravel.log vượt ngưỡng."
    '
}

cleanup_docker() {
    warn 'Docker cleanup là host-wide; chỉ xóa dangling images và build cache, tuyệt đối không prune volumes.'
    docker image prune -f
    docker builder prune -f
    pass 'Docker safe cleanup hoàn tất; named volumes không bị xóa.'
}

case "$MODE" in
    --report) report ;;
    --logs) cleanup_logs; report ;;
    --docker) cleanup_docker; report ;;
    --all) cleanup_logs; cleanup_docker; report ;;
esac
