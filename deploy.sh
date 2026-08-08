#!/usr/bin/env bash

set -Eeuo pipefail

readonly PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly EXPECTED_APP_URL="https://tungocvan.com"
readonly BACKUP_DIR="${PROJECT_DIR}/deploy-backups"
readonly LOCK_FILE="${PROJECT_DIR}/.deploy.lock"

cd "$PROJECT_DIR"

log() {
    printf '\n\033[1;34m[DEPLOY]\033[0m %s\n' "$1"
}

fail() {
    printf '\n\033[1;31m[ERROR]\033[0m %s\n' "$1" >&2
    exit 1
}

on_error() {
    local exit_code=$?

    printf '\n\033[1;31m[FAILED]\033[0m Deploy dừng tại dòng %s (exit code %s).\n' "$1" "$exit_code" >&2
    printf 'Website được giữ ở maintenance mode nếu chế độ này đã được bật.\n' >&2
    docker compose ps 2>/dev/null || true
    exit "$exit_code"
}

trap 'on_error $LINENO' ERR

command -v docker >/dev/null 2>&1 || fail 'Chưa cài Docker.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose plugin chưa sẵn sàng.'
command -v flock >/dev/null 2>&1 || fail 'Thiếu lệnh flock (util-linux).'
[[ -f .env ]] || fail 'Không tìm thấy .env. Hãy copy .env.docker.example thành .env và cấu hình secret.'

exec 9>"$LOCK_FILE"
flock -n 9 || fail 'Một tiến trình deploy khác đang chạy.'

app_url="$(sed -n 's/^APP_URL=//p' .env | tail -n 1 | tr -d '\r' | sed -e 's/^"//' -e 's/"$//')"
[[ "$app_url" == "$EXPECTED_APP_URL" ]] || fail "APP_URL phải là ${EXPECTED_APP_URL}; hiện tại là ${app_url:-<rỗng>}."

log 'Kiểm tra cấu hình Docker Compose'
docker compose config --quiet

if [[ "${1:-}" == '--pull' ]]; then
    log 'Pull source code từ origin/main'
    [[ -z "$(git status --porcelain)" ]] || fail 'Git working tree có thay đổi. Không thể tự động pull.'
    git pull --ff-only origin main
fi

mkdir -p "$BACKUP_DIR"

if [[ -n "$(docker compose ps --status running -q db 2>/dev/null)" ]]; then
    backup_file="${BACKUP_DIR}/before-deploy-$(date +%Y%m%d-%H%M%S).sql"
    log "Backup database vào ${backup_file}"
    docker compose exec -T db sh -lc \
        'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers "$MARIADB_DATABASE"' \
        > "$backup_file"
    [[ -s "$backup_file" ]] || fail 'File backup database rỗng.'
else
    log 'Container database chưa chạy; bỏ qua backup trước deploy đầu tiên'
fi

log 'Pull image MariaDB và Redis'
docker compose pull db redis

log 'Build sạch toàn bộ application images'
docker compose build --pull --no-cache

if [[ -n "$(docker compose ps --status running -q app 2>/dev/null)" ]]; then
    log 'Bật maintenance mode'
    docker compose exec -T app php artisan down --retry=60
fi

log 'Khởi động và recreate toàn bộ services'
docker compose up -d --remove-orphans

log 'Chờ database và Redis healthy'
for attempt in $(seq 1 30); do
    db_health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$(docker compose ps -q db)" 2>/dev/null || true)"
    redis_health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$(docker compose ps -q redis)" 2>/dev/null || true)"

    if [[ "$db_health" == 'healthy' && "$redis_health" == 'healthy' ]]; then
        break
    fi

    [[ "$attempt" -lt 30 ]] || fail "Service chưa healthy: db=${db_health:-unknown}, redis=${redis_health:-unknown}."
    sleep 2
done

log 'Chạy database migrations'
docker compose exec -T app php artisan migrate --force

log 'Làm mới cache Laravel và storage link'
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan storage:link --quiet || true
docker compose exec -T app php artisan optimize

if docker compose exec -T app php artisan list --raw | grep -q '^module:permissions-sync'; then
    log 'Đồng bộ permissions của Modules'
    docker compose exec -T app php artisan module:permissions-sync
fi

log 'Tắt maintenance mode'
docker compose exec -T app php artisan up

log 'Kiểm tra health endpoint'
health_ok=0
for attempt in $(seq 1 20); do
    if curl --fail --silent --show-error http://127.0.0.1:8081/up >/dev/null; then
        health_ok=1
        break
    fi
    sleep 2
done

[[ "$health_ok" -eq 1 ]] || fail 'Health endpoint /up không phản hồi HTTP 200.'

docker compose ps

log 'Deploy hoàn tất thành công'
printf 'Website: %s\n' "$EXPECTED_APP_URL"
printf 'Commit: %s\n' "$(git rev-parse --short HEAD)"
