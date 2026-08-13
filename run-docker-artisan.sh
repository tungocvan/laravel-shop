#!/usr/bin/env bash
set -Eeuo pipefail

# Resolve the project from the directory containing this script.
# Example: /opt/projects/tnv/run-docker-artisan.sh -> tnv-app-1
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "$SCRIPT_DIR")"
CONTAINER="${PROJECT_NAME}-app-1"

if [[ $# -eq 0 ]]; then
    echo "Usage:"
    echo "  ./run-docker-artisan.sh \"php artisan <command>\""
    echo
    echo "Examples:"
    echo "  ./run-docker-artisan.sh \"php artisan db:seed --force\""
    echo "  ./run-docker-artisan.sh \"php artisan migrate --force\""
    echo "  ./run-docker-artisan.sh \"php artisan optimize:clear\""
    exit 1
fi

if ! docker inspect "$CONTAINER" >/dev/null 2>&1; then
    echo "[ERROR] Không tìm thấy container: $CONTAINER"
    exit 1
fi

if [[ "$(docker inspect -f '{{.State.Running}}' "$CONTAINER")" != "true" ]]; then
    echo "[ERROR] Container không chạy: $CONTAINER"
    exit 1
fi

printf '> docker exec -it %s %s\n\n' "$CONTAINER" "$*"
docker exec -it "$CONTAINER" bash -lc "$*"
