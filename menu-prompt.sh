#!/usr/bin/env bash
# Prompt menu for laravel-shop. Read-only; never runs Git/Docker/Artisan.
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROMPT="$ROOT/docs/prompts/PRODUCT_DEBUG_REFACTOR_PROMPT.md"
usage() {
  printf 'Usage: bash menu-prompt.sh [--help|--print-debug-product]\n'
}
print_prompt() {
  if [[ ! -f "$PROMPT" ]]; then
    printf 'Missing prompt template: %s\n' "$PROMPT" >&2
    exit 1
  fi
  printf '\n=== PROMPT DEBUG PRODUCT / FIX / REFACTOR ===\n'
  printf 'Copy the content below into a new ChatGPT conversation.\n\n'
  cat -- "$PROMPT"
  printf '\n=== END OF PROMPT ===\n'
}
case "${1:-}" in
  --help|-h) usage; exit 0 ;;
  --print-debug-product) [[ $# -eq 1 ]] || { usage >&2; exit 2; }; print_prompt; exit 0 ;;
  '') ;;
  *) usage >&2; exit 2 ;;
esac
if [[ ! -t 0 ]]; then
  printf 'Interactive menu requires a terminal. Use --print-debug-product to print directly.\n' >&2
  exit 2
fi
while true; do
  printf '\n=== LARAVEL SHOP — PROMPT MENU ===\n'
  printf '1) Prompt Debug Product / Fix / Refactor Module hoặc chức năng\n'
  printf '0) Thoát\n'
  printf 'Chọn [1/0]: '
  IFS= read -r choice || exit 0
  case "$choice" in
    1) print_prompt ;;
    0) exit 0 ;;
    *) printf 'Lựa chọn không hợp lệ. Vui lòng chọn 1 hoặc 0.\n' ;;
  esac
done
