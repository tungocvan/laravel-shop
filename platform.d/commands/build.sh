#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
ensure_project
if [[ "${1:-}" =~ ^(-h|--help|help)$ ]]; then
cat <<'EOF'
USAGE
  ./platform-cli build [options]

OPTIONS
  --dry-run
  --status
  --all
  --no-cache
  --reset
EOF
exit 0
fi
SMART_BUILD="$PROJECT_DIR/platform/build.sh"
[[ -x "$SMART_BUILD" ]] || die "Không thấy Smart Build tại $SMART_BUILD"
exec "$SMART_BUILD" "$@"
