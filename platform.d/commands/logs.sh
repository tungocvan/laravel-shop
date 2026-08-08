#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
ensure_project
if [[ "${1:-}" =~ ^(-h|--help|help)$ ]]; then
cat <<'EOF'
USAGE
  ./platform-cli logs [service] [--tail=N] [-f]

EXAMPLES
  ./platform-cli logs app
  ./platform-cli logs web --tail=200
  ./platform-cli logs socket -f
  ./platform-cli logs
EOF
exit 0
fi
SERVICE=""
TAIL=100
FOLLOW=0
for arg in "$@"; do
  case "$arg" in
    --tail=*) TAIL="${arg#*=}" ;;
    -f|--follow) FOLLOW=1 ;;
    -*) die "Option không hợp lệ: $arg" ;;
    *) [[ -z "$SERVICE" ]] || die "Chỉ được truyền một service"; SERVICE="$arg" ;;
  esac
done
args=(logs "--tail=$TAIL")
[[ "$FOLLOW" -eq 1 ]] && args+=(-f)
[[ -n "$SERVICE" ]] && args+=("$SERVICE")
exec env PROJECT_DIR="$PROJECT_DIR" "$CENTRAL_PLATFORM_DIR/scripts/compose.sh" "${args[@]}"
