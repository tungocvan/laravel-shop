#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
cd "$SCRIPT_DIR"

if [[ $# -eq 0 ]]; then
    echo 'Usage: ./run-docker-artisan.sh "php artisan <command>"'
    exit 1
fi

docker compose version >/dev/null 2>&1 || {
    echo '[ERROR] Docker Compose plugin không khả dụng.'
    exit 1
}

compose() {
    docker compose -p "$PROJECT_NAME" "$@"
}

CONTAINER="$(compose ps -q app 2>/dev/null | head -1)"
[[ -n "$CONTAINER" ]] || {
    echo "[ERROR] Không resolve được Compose service app của project: $PROJECT_NAME"
    exit 1
}

[[ "$(docker inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null)" == 'true' ]] || {
    echo "[ERROR] Compose app container không chạy cho project: $PROJECT_NAME"
    exit 1
}

CONTAINER_NAME="$(docker inspect -f '{{.Name}}' "$CONTAINER" 2>/dev/null | sed 's#^/##')"
printf '> compose-project=%s container=%s\n> %s\n\n' "$PROJECT_NAME" "$CONTAINER_NAME" "$*"
docker exec -it "$CONTAINER" bash -lc "$*"
