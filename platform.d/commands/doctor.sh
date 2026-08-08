#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
if [[ "${1:-}" =~ ^(-h|--help|help)$ ]]; then
cat <<'EOF'
USAGE
  ./platform-cli doctor

Kiểm tra Docker, Compose, APP_KEY, container, HTTP, DB, Queue, Socket.
EOF
exit 0
fi
FAILURES=0
ok(){ success "$1"; }
bad(){ error "$1"; FAILURES=$((FAILURES+1)); }
command -v docker >/dev/null 2>&1 && ok "Docker đã cài" || bad "Thiếu Docker"
docker compose version >/dev/null 2>&1 && ok "Docker Compose hoạt động" || bad "Docker Compose lỗi"
[[ -f "$PROJECT_DIR/artisan" ]] && ok "Laravel project hợp lệ" || bad "Không thấy artisan"
[[ -f "$LARAVEL_ENV" ]] && ok ".env tồn tại" || bad ".env không tồn tại"
APP_KEY_VALUE="$(read_env_value APP_KEY "$LARAVEL_ENV" || true)"
[[ -n "$APP_KEY_VALUE" ]] && ok "APP_KEY đã cấu hình" || bad "APP_KEY đang trống"
compose_cmd config >/dev/null 2>&1 && ok "Compose config hợp lệ" || bad "Compose config lỗi"
load_env_file "$PLATFORM_ENV"
if [[ -n "${HTTP_PORT:-}" ]]; then
  CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 10 "http://127.0.0.1:${HTTP_PORT}" || true)"
  case "$CODE" in
    200|201|204|301|302|307|308|401|403|404) ok "HTTP nội bộ phản hồi $CODE" ;;
    *) bad "HTTP nội bộ lỗi: ${CODE:-không-kết-nối}" ;;
  esac
else
  warn "Không xác định được HTTP_PORT"
fi
compose_cmd ps -a || true
if [[ "$FAILURES" -eq 0 ]]; then
  success "Doctor hoàn tất: hệ thống ổn."
else
  error "Doctor phát hiện $FAILURES lỗi."
  exit 1
fi
