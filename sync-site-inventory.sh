#!/usr/bin/env bash
set -Eeuo pipefail

PLATFORM_HOME="${PLATFORM_HOME:-/opt/laravel-deployment-platform}"
INVENTORY_FILE="${INVENTORY_FILE:-$PLATFORM_HOME/state/sites.json}"
DOCKER_PLATFORM_DIR="${DOCKER_PLATFORM_DIR:-/opt/laravel-docker-platform}"

info(){ printf '\033[1;34m[INFO]\033[0m %s\n' "$*"; }
ok(){ printf '\033[1;32m[OK]\033[0m %s\n' "$*"; }
warn(){ printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
die(){ printf '\033[1;31m[ERROR]\033[0m %s\n' "$*" >&2; exit 1; }

[[ "${EUID:-$(id -u)}" -eq 0 ]] || die "Hãy chạy bằng sudo/root."
[[ $# -eq 1 ]] || die "USAGE: sudo $0 <site-name|domain|project-path>"
[[ -f "$INVENTORY_FILE" ]] || die "Không tìm thấy inventory: $INVENTORY_FILE"
[[ -x "$DOCKER_PLATFORM_DIR/scripts/compose.sh" ]] || die "Không tìm thấy compose.sh"

KEY="$1"

SITE_JSON="$(python3 - "$INVENTORY_FILE" "$KEY" <<'PY'
import json,sys
p,k=sys.argv[1:]
with open(p,encoding='utf-8') as f: data=json.load(f)
for s in data.get('sites',[]):
    if k in (s.get('name'),s.get('domain'),s.get('path')):
        print(json.dumps(s,ensure_ascii=False)); raise SystemExit
raise SystemExit(1)
PY
)" || die "Không tìm thấy site: $KEY"

PROJECT_DIR="$(python3 -c 'import json,sys; print(json.load(sys.stdin)["path"])' <<<"$SITE_JSON")"
SITE_NAME="$(python3 -c 'import json,sys; print(json.load(sys.stdin)["name"])' <<<"$SITE_JSON")"

[[ -d "$PROJECT_DIR" ]] || die "Project path không tồn tại: $PROJECT_DIR"
[[ -f "$PROJECT_DIR/.env" ]] || die "Không tìm thấy $PROJECT_DIR/.env"

read_env(){
  local key="$1" file="$2"
  [[ -f "$file" ]] || return 1
  sed -n -E "s/^${key}=(.*)$/\1/p" "$file" | tail -n1 | sed -E 's/^"(.*)"$/\1/; s/^'\''(.*)'\''$/\1/'
}

compose(){ PROJECT_DIR="$PROJECT_DIR" "$DOCKER_PLATFORM_DIR/scripts/compose.sh" "$@"; }

APP_URL="$(read_env APP_URL "$PROJECT_DIR/.env" || true)"
DOMAIN="${APP_URL#http://}"; DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN%%/*}"
DB_DATABASE="$(read_env DB_DATABASE "$PROJECT_DIR/.env" || true)"
HTTP_ENV="$(read_env HTTP_PORT "$PROJECT_DIR/.docker-platform.env" || true)"
SOCKET_ENV="$(read_env SOCKET_PORT "$PROJECT_DIR/.docker-platform.env" || true)"

REPO=""; BRANCH=""; COMMIT=""
if [[ -d "$PROJECT_DIR/.git" ]]; then
  REPO="$(git -C "$PROJECT_DIR" remote get-url origin 2>/dev/null || true)"
  BRANCH="$(git -C "$PROJECT_DIR" branch --show-current 2>/dev/null || true)"
  COMMIT="$(git -C "$PROJECT_DIR" rev-parse HEAD 2>/dev/null || true)"
fi

CONFIG="$(compose config)" || die "Compose config lỗi"
HTTP_COMPOSE="$(awk '/^  web:/{f=1;next} f&&/^[^ ]/{f=0} f&&/published:/{gsub(/[^0-9]/,""); print; exit}' <<<"$CONFIG")"
SOCKET_COMPOSE="$(awk '/^  socket:/{f=1;next} f&&/^[^ ]/{f=0} f&&/published:/{gsub(/[^0-9]/,""); print; exit}' <<<"$CONFIG")"
HTTP_PORT="${HTTP_COMPOSE:-$HTTP_ENV}"
SOCKET_PORT="${SOCKET_COMPOSE:-$SOCKET_ENV}"
[[ -n "$HTTP_PORT" ]] || die "Không xác định được HTTP port"

STATUS="inactive"
if compose ps -a web 2>/dev/null | grep -Eq 'Up|healthy'; then STATUS="active"; fi
HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 8 -H "Host: ${DOMAIN:-localhost}" "http://127.0.0.1:${HTTP_PORT}" 2>/dev/null || true)"
case "$HTTP_CODE" in 200|201|204|301|302|307|308|401|403|404) STATUS="active";; esac

info "Phát hiện:"
printf '  %-12s %s\n' "Name:" "$SITE_NAME"
printf '  %-12s %s\n' "Domain:" "${DOMAIN:-không rõ}"
printf '  %-12s %s\n' "Repo:" "${REPO:-không rõ}"
printf '  %-12s %s\n' "Branch:" "${BRANCH:-không rõ}"
printf '  %-12s %s\n' "HTTP port:" "$HTTP_PORT"
printf '  %-12s %s\n' "Socket port:" "${SOCKET_PORT:--}"
printf '  %-12s %s\n' "Database:" "${DB_DATABASE:-không rõ}"
printf '  %-12s %s\n' "Status:" "$STATUS"

BACKUP="${INVENTORY_FILE}.bak.$(date +%Y%m%d_%H%M%S)"
cp "$INVENTORY_FILE" "$BACKUP"
info "Backup inventory: $BACKUP"

python3 - "$INVENTORY_FILE" "$KEY" "$DOMAIN" "$PROJECT_DIR" "$HTTP_PORT" "${SOCKET_PORT:-}" "$DB_DATABASE" "$REPO" "$BRANCH" "$COMMIT" "$STATUS" <<'PY'
import json,sys,os
from datetime import datetime,timezone
p,key,domain,path,http,socket,db,repo,branch,commit,status=sys.argv[1:]
with open(p,encoding='utf-8') as f: data=json.load(f)
for s in data.get('sites',[]):
    if key in (s.get('name'),s.get('domain'),s.get('path')):
        s.update({
          'domain': domain or s.get('domain'),
          'path': path,
          'http_port': int(http),
          'socket_port': int(socket) if socket else None,
          'database': db or s.get('database'),
          'repo': repo or s.get('repo'),
          'branch': branch or s.get('branch'),
          'commit': commit or s.get('commit'),
          'status': status,
          'last_synced_at': datetime.now(timezone.utc).isoformat(),
        })
        break
else:
    raise SystemExit('site not found')
tmp=p+'.tmp'
with open(tmp,'w',encoding='utf-8') as f:
    json.dump(data,f,ensure_ascii=False,indent=2); f.write('\n')
os.replace(tmp,p)
PY

ok "Đã cập nhật inventory cho site: $SITE_NAME"
platform site show "$SITE_NAME"
