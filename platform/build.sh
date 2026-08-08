#!/usr/bin/env bash
set -Eeuo pipefail

# ============================================================
# Docker Platform Smart Build
#
# Chạy trong project:
#   ./platform/build.sh
#
# Tùy chọn:
#   --dry-run     Chỉ hiển thị kế hoạch
#   --all         Build lại toàn bộ service đang bật
#   --no-cache    Build không dùng cache
#   --reset       Xóa trạng thái build cũ, lần sau build toàn bộ
#   --status      Chỉ xem thay đổi được phát hiện
# ============================================================

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLATFORM_DIR="${PLATFORM_DIR:-/opt/laravel-docker-platform}"
STATE_DIR="$PROJECT_DIR/.docker-platform/state"
HASH_DIR="$STATE_DIR/hashes"
LOCK_FILE="$STATE_DIR/build.lock"

DRY_RUN=0
FORCE_ALL=0
NO_CACHE=0
RESET_STATE=0
STATUS_ONLY=0

log()  { printf '\033[1;34m[INFO]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[OK]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m[ERROR]\033[0m %s\n' "$*" >&2; exit 1; }

usage() {
    cat <<'EOF'
Dùng:
  ./platform/build.sh [tùy chọn]

Tùy chọn:
  --dry-run     Chỉ xem kế hoạch, không build/recreate
  --all         Build lại toàn bộ service đang bật
  --no-cache    Build sạch, không dùng Docker cache
  --reset       Xóa trạng thái build cũ
  --status      Chỉ xem nhóm file nào đã thay đổi
  -h, --help
EOF
}

for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --all) FORCE_ALL=1 ;;
        --no-cache) NO_CACHE=1 ;;
        --reset) RESET_STATE=1 ;;
        --status) STATUS_ONLY=1 ;;
        -h|--help) usage; exit 0 ;;
        *) die "Tùy chọn không hợp lệ: $arg" ;;
    esac
done

[[ -d "$PLATFORM_DIR" ]] || die "Không thấy Docker Platform: $PLATFORM_DIR"
[[ -x "$PLATFORM_DIR/scripts/compose.sh" ]] || die "Thiếu compose.sh trong Docker Platform"
[[ -x "$PLATFORM_DIR/scripts/preflight.sh" ]] || die "Thiếu preflight.sh trong Docker Platform"

mkdir -p "$HASH_DIR"

if [[ "$RESET_STATE" -eq 1 ]]; then
    rm -rf "$STATE_DIR"
    mkdir -p "$HASH_DIR"
    ok "Đã xóa trạng thái build cũ."
    [[ "$STATUS_ONLY" -eq 1 ]] && exit 0
fi

# Ngăn chạy 2 build đồng thời.
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    die "Một tiến trình build khác đang chạy cho project này."
fi

cd "$PROJECT_DIR"

# Nạp cấu hình platform project.
if [[ -f "$PROJECT_DIR/.docker-platform.env" ]]; then
    set -a
    # shellcheck disable=SC1091
    source "$PROJECT_DIR/.docker-platform.env"
    set +a
fi

compose() {
    PROJECT_DIR="$PROJECT_DIR" "$PLATFORM_DIR/scripts/compose.sh" "$@"
}

# Trả về danh sách service thật sự đang được ghép bởi feature detection.
mapfile -t ACTIVE_SERVICES < <(compose config --services)
((${#ACTIVE_SERVICES[@]} > 0)) || die "Không phát hiện service Docker Compose."

has_service() {
    local wanted="$1" item
    for item in "${ACTIVE_SERVICES[@]}"; do
        [[ "$item" == "$wanted" ]] && return 0
    done
    return 1
}

# Tạo fingerprint ổn định cho một nhóm đường dẫn.
# Bao gồm cả tên file và nội dung; file bị xóa cũng làm fingerprint thay đổi.
fingerprint() {
    local group="$1"
    shift

    {
        printf 'GROUP:%s\n' "$group"

        local path
        for path in "$@"; do
            if [[ -f "$path" ]]; then
                printf 'FILE:%s\n' "$path"
                sha256sum "$path"
            elif [[ -d "$path" ]]; then
                find "$path" \
                    -type f \
                    ! -path '*/vendor/*' \
                    ! -path '*/node_modules/*' \
                    ! -path '*/storage/logs/*' \
                    ! -path '*/storage/framework/cache/*' \
                    ! -path '*/storage/framework/sessions/*' \
                    ! -path '*/storage/framework/views/*' \
                    ! -path '*/bootstrap/cache/*' \
                    ! -path '*/public/build/*' \
                    ! -path '*/.git/*' \
                    -print0 |
                sort -z |
                while IFS= read -r -d '' file; do
                    printf 'FILE:%s\n' "$file"
                    sha256sum "$file"
                done
            else
                printf 'MISSING:%s\n' "$path"
            fi
        done
    } | sha256sum | awk '{print $1}'
}

current_hash() {
    local group="$1"
    case "$group" in
        app)
            fingerprint app \
                artisan composer.json composer.lock \
                app bootstrap config database routes \
                Modules
            ;;
        frontend)
            fingerprint frontend \
                package.json package-lock.json \
                vite.config.js vite.config.ts \
                tailwind.config.js tailwind.config.cjs \
                postcss.config.js \
                resources \
                Modules
            ;;
        socket)
            fingerprint socket socket
            ;;
        docker)
            fingerprint docker \
                Dockerfile .dockerignore \
                compose.yaml compose.yml \
                compose.queue.yaml compose.scheduler.yaml compose.socket.yaml \
                docker \
                .docker-platform.env
            ;;
        env)
            fingerprint env .env
            ;;
        *)
            die "Nhóm fingerprint không hợp lệ: $group"
            ;;
    esac
}

read_old_hash() {
    local group="$1"
    [[ -f "$HASH_DIR/$group.sha256" ]] && cat "$HASH_DIR/$group.sha256" || true
}

declare -A NEW_HASH OLD_HASH CHANGED
HASH_GROUPS=(app frontend socket docker env)

FIRST_BUILD=0
[[ -f "$STATE_DIR/last-successful-build" ]] || FIRST_BUILD=1

for group in "${HASH_GROUPS[@]}"; do
    NEW_HASH["$group"]="$(current_hash "$group")"
    OLD_HASH["$group"]="$(read_old_hash "$group")"

    if [[ "$FORCE_ALL" -eq 1 || "$FIRST_BUILD" -eq 1 || "${NEW_HASH[$group]}" != "${OLD_HASH[$group]}" ]]; then
        CHANGED["$group"]=1
    else
        CHANGED["$group"]=0
    fi
done

# Nếu project không có socket active thì bỏ thay đổi socket.
if ! has_service socket; then
    CHANGED[socket]=0
fi

show_detection() {
    echo
    echo "================ SMART BUILD ================"
    printf 'Project: %s\n' "$PROJECT_DIR"
    printf 'Lần build đầu: %s\n' "$([[ "$FIRST_BUILD" -eq 1 ]] && echo Có || echo Không)"
    printf 'Service đang bật: %s\n' "${ACTIVE_SERVICES[*]}"
    echo "---------------------------------------------"
    local group
    for group in "${HASH_GROUPS[@]}"; do
        printf '%-10s : %s\n' "$group" "$([[ "${CHANGED[$group]}" -eq 1 ]] && echo THAY ĐỔI || echo không đổi)"
    done
    echo "============================================="
}

show_detection

if [[ "$STATUS_ONLY" -eq 1 ]]; then
    exit 0
fi

# Service cần build và service cần recreate.
declare -a BUILD_SERVICES=()
declare -a RECREATE_SERVICES=()

add_unique() {
    local array_name="$1" value="$2"
    local -n arr="$array_name"
    local item
    for item in "${arr[@]:-}"; do
        [[ "$item" == "$value" ]] && return 0
    done
    arr+=("$value")
}

add_if_active() {
    local array_name="$1" service="$2"
    has_service "$service" && add_unique "$array_name" "$service"
}

# Docker/platform thay đổi: build mọi image buildable đang hoạt động.
if [[ "${CHANGED[docker]}" -eq 1 || "$FORCE_ALL" -eq 1 ]]; then
    for service in app web queue scheduler socket; do
        add_if_active BUILD_SERVICES "$service"
        add_if_active RECREATE_SERVICES "$service"
    done
fi

# PHP/Laravel thay đổi:
# app + queue + scheduler chứa source PHP.
if [[ "${CHANGED[app]}" -eq 1 ]]; then
    for service in app queue scheduler; do
        add_if_active BUILD_SERVICES "$service"
        add_if_active RECREATE_SERVICES "$service"
    done

    # Web cần recreate để cập nhật upstream khi app đổi container/IP.
    add_if_active RECREATE_SERVICES web
fi

# Frontend thay đổi: Vite assets nằm trong app và web.
if [[ "${CHANGED[frontend]}" -eq 1 ]]; then
    for service in app web; do
        add_if_active BUILD_SERVICES "$service"
        add_if_active RECREATE_SERVICES "$service"
    done
fi

# Socket source thay đổi.
if [[ "${CHANGED[socket]}" -eq 1 ]]; then
    add_if_active BUILD_SERVICES socket
    add_if_active RECREATE_SERVICES socket
fi

# .env thay đổi: không cần build image, nhưng cần recreate runtime service.
if [[ "${CHANGED[env]}" -eq 1 ]]; then
    for service in app web queue scheduler socket; do
        add_if_active RECREATE_SERVICES "$service"
    done

    # DB/Redis chỉ được recreate khi người dùng cho phép rõ ràng.
    if [[ "${RECREATE_INFRA_ON_ENV_CHANGE:-false}" == "true" ]]; then
        add_if_active RECREATE_SERVICES db
        add_if_active RECREATE_SERVICES redis
    else
        warn ".env thay đổi: mặc định không recreate db/redis để tránh gián đoạn dữ liệu."
        warn "Đặt RECREATE_INFRA_ON_ENV_CHANGE=true nếu thực sự đổi cấu hình DB/Redis container."
    fi
fi

echo
printf 'Sẽ build    : %s\n' "${BUILD_SERVICES[*]:-(không có)}"
printf 'Sẽ recreate : %s\n' "${RECREATE_SERVICES[*]:-(không có)}"
echo

if ((${#BUILD_SERVICES[@]} == 0 && ${#RECREATE_SERVICES[@]} == 0)); then
    ok "Không phát hiện thay đổi cần build hoặc recreate."
    compose ps
    exit 0
fi

if [[ "$DRY_RUN" -eq 1 ]]; then
    ok "Dry-run hoàn tất; chưa thay đổi container."
    exit 0
fi

log "Chạy preflight..."
PROJECT_DIR="$PROJECT_DIR" "$PLATFORM_DIR/scripts/preflight.sh"

BUILD_ARGS=()
[[ "$NO_CACHE" -eq 1 ]] && BUILD_ARGS+=(--no-cache)

if ((${#BUILD_SERVICES[@]} > 0)); then
    log "Build: ${BUILD_SERVICES[*]}"
    compose build "${BUILD_ARGS[@]}" "${BUILD_SERVICES[@]}"
fi

if ((${#RECREATE_SERVICES[@]} > 0)); then
    log "Recreate: ${RECREATE_SERVICES[*]}"
    # Yêu cầu Laravel Queue Worker tự thoát an toàn.
if has_service queue; then
    log "Yêu cầu Queue Worker dừng an toàn..."

    compose exec -T app php artisan queue:restart || true

    # Cho worker vài giây để nhận tín hiệu restart.
    sleep 5

    # Không chờ vô hạn nếu worker đang bị kẹt.
    compose stop -t 15 queue || true
fi		
declare -a NORMAL_RECREATE=()
RECREATE_QUEUE=0

for service in "${RECREATE_SERVICES[@]}"; do
    if [[ "$service" == "queue" ]]; then
        RECREATE_QUEUE=1
    else
        NORMAL_RECREATE+=("$service")
    fi
done

if ((${#NORMAL_RECREATE[@]} > 0)); then
    compose up -d \
        --force-recreate \
        --remove-orphans \
        "${NORMAL_RECREATE[@]}"
fi

if [[ "$RECREATE_QUEUE" -eq 1 ]]; then
    log "Recreate Queue riêng..."
    compose rm -f -s queue || true
    compose up -d --force-recreate --no-deps queue
fi
else
    compose up -d --remove-orphans
fi

# Xóa cache Laravel sau khi app chạy.
if has_service app; then
    log "Xóa cache Laravel..."
    compose exec -T app php artisan optimize:clear
fi

# Queue worker nhận code/config mới sạch sẽ.
if has_service queue; then
    compose exec -T app php artisan queue:restart || true
fi

# Chỉ lưu trạng thái khi tất cả bước bắt buộc đã thành công.
for group in "${HASH_GROUPS[@]}"; do
    printf '%s\n' "${NEW_HASH[$group]}" > "$HASH_DIR/$group.sha256"
done

date -Iseconds > "$STATE_DIR/last-successful-build"

if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    git rev-parse HEAD > "$STATE_DIR/last-git-commit" || true
fi

echo
compose ps
ok "Smart build hoàn tất và đã lưu trạng thái."
