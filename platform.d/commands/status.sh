#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
ensure_project
if [[ "${1:-}" =~ ^(-h|--help|help)$ ]]; then
cat <<'EOF'
USAGE
  ./platform-cli status
EOF
exit 0
fi
load_env_file "$PLATFORM_ENV"
echo "Project   : $PROJECT_DIR"
echo "APP_URL   : $(read_env_value APP_URL "$LARAVEL_ENV" || echo chưa-cấu-hình)"
echo "HTTP_PORT : ${HTTP_PORT:-chưa-cấu-hình}"
echo
echo "Services:"
compose_cmd config --services | sed 's/^/  - /'
echo
compose_cmd ps -a
