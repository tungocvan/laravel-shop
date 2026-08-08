#!/usr/bin/env bash
PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
CENTRAL_PLATFORM_DIR="${CENTRAL_PLATFORM_DIR:-/opt/laravel-docker-platform}"
PLATFORM_ENV="$PROJECT_DIR/.docker-platform.env"
LARAVEL_ENV="$PROJECT_DIR/.env"

info()    { printf '\033[1;34m[INFO]\033[0m %s\n' "$*"; }
success() { printf '\033[1;32m[OK]\033[0m %s\n' "$*"; }
warn()    { printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
error()   { printf '\033[1;31m[ERROR]\033[0m %s\n' "$*" >&2; }
die()     { error "$*"; exit 1; }

load_env_file() {
  local file="$1"
  [[ -f "$file" ]] || return 0
  set -a
  source "$file"
  set +a
}

compose_cmd() {
  PROJECT_DIR="$PROJECT_DIR" "$CENTRAL_PLATFORM_DIR/scripts/compose.sh" "$@"
}

service_exists() {
  compose_cmd config --services 2>/dev/null | grep -qx "$1"
}

ensure_project() {
  [[ -f "$PROJECT_DIR/artisan" ]] || die "Không thấy artisan trong $PROJECT_DIR"
  [[ -x "$CENTRAL_PLATFORM_DIR/scripts/compose.sh" ]] || die "Thiếu compose.sh tại $CENTRAL_PLATFORM_DIR"
}

read_env_value() {
  local key="$1"
  local file="${2:-$LARAVEL_ENV}"
  [[ -f "$file" ]] || return 1
  sed -n -E "s/^${key}=(.*)$/\1/p" "$file" | tail -n 1 | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'
}

confirm() {
  local answer
  read -r -p "${1:-Tiếp tục?} [y/N]: " answer
  [[ "$answer" =~ ^[Yy]$ ]]
}
