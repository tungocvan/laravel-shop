#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
bash -n "$ROOT/menu-prompt.sh"
bash "$ROOT/menu-prompt.sh" --help | grep -Fq -- '--project PROJECT'
for project in tnv ntd; do
  output="$(bash "$ROOT/menu-prompt.sh" --project "$project")"
  [[ "$output" == *"COPY PROMPT — PRODUCTION DEBUG: $project"* ]]
  [[ "$output" == *'Production Docker Debug Master Prompt'* ]]
  [[ "$output" == *"PROJECT PROFILE: docs/projects/$project.md"* ]]
  [[ "$output" == *'MỤC TIÊU DEBUG HIỆN TẠI'* ]]
  [[ "$output" == *'=== END OF PROMPT ==='* ]]
done
[[ "$(bash "$ROOT/menu-prompt.sh" --project unknownproject)" == *'PROFILE_NOT_FOUND'* ]]
if bash "$ROOT/menu-prompt.sh" --project ../tnv >/dev/null 2>&1; then
  echo 'Path traversal project must fail' >&2
  exit 1
fi
if bash "$ROOT/menu-prompt.sh" --invalid >/dev/null 2>&1; then
  echo 'Invalid flag must fail' >&2
  exit 1
fi
if bash "$ROOT/menu-prompt.sh" </dev/null >/dev/null 2>&1; then
  echo 'Noninteractive menu without project must fail' >&2
  exit 1
fi
echo '[OK] Production prompt menu contract'
