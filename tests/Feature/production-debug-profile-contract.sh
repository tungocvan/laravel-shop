#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
bash -n "$ROOT/production-debug.sh"
bash -n "$ROOT/production-debug-profile.sh"
grep -Fq 'exec bash "$SCRIPT_DIR/production-debug-profile.sh" "$@"' "$ROOT/production-debug.sh"
grep -Fq 'label=com.docker.compose.project=' "$ROOT/production-debug-profile.sh"
grep -Fq 'label=com.docker.compose.service=app' "$ROOT/production-debug-profile.sh"
grep -Fq 'Refusing to overwrite existing report' "$ROOT/production-debug-profile.sh"
grep -Fq 'docs/projects/<project>.md' "$ROOT/docs/PRODUCTION_DEBUG_PROMPT.md"
bash "$ROOT/production-debug-profile.sh" --help | grep -Fq -- '--project-dir'
if bash "$ROOT/production-debug-profile.sh" --unexpected >/dev/null 2>&1; then
  echo 'Unexpected flag must fail' >&2
  exit 1
fi
# Regression: a blank line in Docker mount output must not trip set -e/pipefail.
# Run the exact guarded loop shape with a trailing empty line.
mount_lines="$(printf 'bind -> /var/www/html/Modules\\n\\n')"
mount_output="$(printf '%s\\n\\n' "$mount_lines" | while IFS= read -r line; do
  if [[ -n "$line" ]]; then
    printf -- '- `%s`\\n' "$line"
  fi
done)"
[[ "$mount_output" == '- `bind -> /var/www/html/Modules`' ]]
grep -Fq 'if [[ -n "$line" ]]; then' "$ROOT/production-debug-profile.sh"
echo '[OK] Production project profile contract'
