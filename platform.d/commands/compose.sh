#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
ensure_project
if [[ "${1:-}" =~ ^(-h|--help|help)$ || $# -eq 0 ]]; then
cat <<'EOF'
USAGE
  ./platform-cli compose <docker-compose-command> [args]

EXAMPLES
  ./platform-cli compose ps
  ./platform-cli compose ps -a
  ./platform-cli compose logs --tail=100 app
  ./platform-cli compose exec app php artisan migrate:status
  ./platform-cli compose restart web
EOF
exit 0
fi
exec env PROJECT_DIR="$PROJECT_DIR" "$CENTRAL_PLATFORM_DIR/scripts/compose.sh" "$@"
