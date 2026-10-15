#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
bash -n "$ROOT/menu-prompt.sh"
help="$(bash "$ROOT/menu-prompt.sh" --help)"
[[ "$help" == *'--print-debug-product'* ]]
output="$(bash "$ROOT/menu-prompt.sh" --print-debug-product)"
[[ "$output" == *'MASTER PROMPT — DEBUG / FIX / REFACTOR MODULE'* ]]
[[ "$output" == *'docs/GITHUB_COLLABORATION_WORKFLOW.md'* ]]
[[ "$output" == *'docs/modules/ClientPortal/PWA_AI_WORKFLOW.md'* ]]
[[ "$output" == *'YÊU CẦU HIỆN TẠI'* ]]
[[ "$output" == *'=== END OF PROMPT ==='* ]]
if bash "$ROOT/menu-prompt.sh" --invalid >/dev/null 2>&1; then
  echo 'Invalid argument must fail' >&2
  exit 1
fi
if bash "$ROOT/menu-prompt.sh" </dev/null >/dev/null 2>&1; then
  echo 'Noninteractive mode must require --print-debug-product' >&2
  exit 1
fi
echo '[OK] Prompt menu contract'
