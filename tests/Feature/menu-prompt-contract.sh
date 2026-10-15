#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "$0")/../.." && pwd)"
SCRIPT="$ROOT/menu-prompt.sh"
bash -n "$SCRIPT"
bash "$SCRIPT" --help | grep -Fq -- '--mode quick|debug|audit'
for project in tnv ntd; do
  for mode in quick debug audit; do
    output="$(bash "$SCRIPT" --project "$project" --mode "$mode")"
    [[ "$output" == *"COPY PROMPT — "*" — $project"* ]]
    [[ "$output" == *'Bạn muốn tôi xử lý vấn đề hoặc chức năng gì?'* ]]
    [[ "$output" == *'docs/GITHUB_COLLABORATION_WORKFLOW.md'* ]]
    [[ "$output" == *'KHÔNG merge'* ]]
    [[ "$output" == *'=== END OF PROMPT ==='* ]]
    [[ "$output" != *'Production Docker Debug Master Prompt'* ]]
    [[ "$output" != *'[ĐIỀN]'* ]]
    [[ "$output" != *'INCIDENT HIỆN TẠI'* ]]
    [[ "$output" != *'PROJECT PROFILE:'* ]]
  done
done
[[ "$(bash "$SCRIPT" --project tnv)" == *'Bạn muốn tôi xử lý vấn đề hoặc chức năng gì?'* ]]
if bash "$SCRIPT" --project tnv --goal 'không hợp lệ' >/dev/null 2>&1; then echo 'Goal CLI input must be rejected' >&2; exit 1; fi
[[ "$(grep -c 'IFS= read -r' "$SCRIPT")" -eq 3 ]]
[[ "$(bash "$SCRIPT" --project unknownproject)" == *'PROFILE_NOT_FOUND'* ]]
if bash "$SCRIPT" --project ../tnv >/dev/null 2>&1; then echo 'Traversal must fail' >&2; exit 1; fi
if bash "$SCRIPT" --project tnv --mode invalid >/dev/null 2>&1; then echo 'Invalid mode must fail' >&2; exit 1; fi
if bash "$SCRIPT" --invalid >/dev/null 2>&1; then echo 'Invalid flag must fail' >&2; exit 1; fi
if bash "$SCRIPT" </dev/null >/dev/null 2>&1; then echo 'Noninteractive without project must fail' >&2; exit 1; fi
echo '[OK] Three-mode prompt menu contract'
