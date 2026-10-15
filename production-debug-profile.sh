#!/usr/bin/env bash
# Read-only, allowlisted runtime inventory. No secrets, logs or Compose config dump.
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$ROOT"
OUTPUT=""
while (($#)); do
  case "$1" in
    --project-dir) [[ $# -ge 2 ]] || { echo "Missing --project-dir value" >&2; exit 2; }; PROJECT_DIR="$2"; shift 2 ;;
    --output) [[ $# -ge 2 ]] || { echo "Missing --output value" >&2; exit 2; }; OUTPUT="$2"; shift 2 ;;
    -h|--help) echo "Usage: $0 [--project-dir /opt/projects/<site>] [--output /path/report.md]"; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; exit 2 ;;
  esac
done
PROJECT_DIR="$(cd -- "$PROJECT_DIR" && pwd -P)"
PROJECT="$(basename "$PROJECT_DIR")"
[[ "$PROJECT" =~ ^[a-zA-Z0-9][a-zA-Z0-9_-]*$ ]] || { echo "Unsafe project name" >&2; exit 2; }
command -v docker >/dev/null || { echo "docker unavailable" >&2; exit 1; }
command -v git >/dev/null || { echo "git unavailable" >&2; exit 1; }
if [[ -n "$OUTPUT" ]]; then
  [[ ! -e "$OUTPUT" ]] || { echo "Refusing to overwrite existing report: $OUTPUT" >&2; exit 2; }
  [[ -d "$(dirname "$OUTPUT")" ]] || { echo "Output directory missing" >&2; exit 2; }
fi
render() {
  printf '# Production runtime profile — %s\n\n' "$PROJECT"
  printf '> Snapshot at %s. READ-ONLY. This is not proof of HTTP/UI health. Revalidate after deploy or runtime changes.\n\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
  printf '## Identity and Git\n\n'
  printf -- '- Project directory: `%s`\n- Compose project: `%s`\n' "$PROJECT_DIR" "$PROJECT"
  local head branch dirty
  head="$(git -C "$PROJECT_DIR" rev-parse --short HEAD 2>/dev/null || echo UNKNOWN)"
  branch="$(git -C "$PROJECT_DIR" branch --show-current 2>/dev/null || echo UNKNOWN)"
  dirty="$(git -C "$PROJECT_DIR" status --porcelain --untracked-files=no 2>/dev/null | wc -l | tr -d ' ')"
  printf -- '- Host Git HEAD: `%s`\n- Branch: `%s`\n- Tracked changes count: `%s`\n' "$head" "$branch" "$dirty"
  printf '\n## Docker runtime\n\n'
  printf -- '- Docker: `%s`\n' "$(docker --version 2>/dev/null || echo UNAVAILABLE)"
  printf -- '- Compose: `%s`\n' "$(docker compose version 2>/dev/null || echo UNAVAILABLE)"
  printf '\n### Services (service | state | health | image ID prefix)\n\n'
  local ids id svc state health img
  ids="$(docker ps -aq --filter "label=com.docker.compose.project=$PROJECT" 2>/dev/null)" || ids=""
  if [[ -z "$ids" ]]; then
    printf 'NO_CONTAINERS_FOUND — verify Compose project identity.\n'
  else
    while IFS= read -r id; do
      [[ -n "$id" ]] || continue
      svc="$(docker inspect -f '{{index .Config.Labels "com.docker.compose.service"}}' "$id" 2>/dev/null || echo UNKNOWN)"
      state="$(docker inspect -f '{{.State.Status}}' "$id" 2>/dev/null || echo UNKNOWN)"
      health="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}n/a{{end}}' "$id" 2>/dev/null || echo UNKNOWN)"
      img="$(docker inspect -f '{{.Image}}' "$id" 2>/dev/null || echo UNKNOWN)"
      printf -- '- `%s | %s | %s | %.19s`\n' "$svc" "$state" "$health" "$img"
    done <<< "$ids"
  fi
  printf '\n### Application container\n\n'
  local app
  app="$(docker ps -q --filter "label=com.docker.compose.project=$PROJECT" --filter 'label=com.docker.compose.service=app' | head -1)"
  if [[ -z "$app" ]]; then
    printf 'APP_NOT_RUNNING. Cannot verify Laravel/PHP/mounts.\n'
  else
    printf -- '- Container ID prefix: `%.12s`\n' "$app"
    printf -- '- Working directory: `%s`\n' "$(docker inspect -f '{{.Config.WorkingDir}}' "$app" 2>/dev/null || echo UNKNOWN)"
    printf -- '- Runtime user (Docker config): `%s`\n' "$(docker inspect -f '{{.Config.User}}' "$app" 2>/dev/null || echo UNKNOWN)"
    printf '\n### Mounts (type and destination only; source paths omitted)\n\n'
    docker inspect -f '{{range .Mounts}}{{printf "%s -> %s\\n" .Type .Destination}}{{end}}' "$app" 2>/dev/null | while IFS= read -r line; do
      [[ -n "$line" ]] && printf -- '- `%s`\n' "$line"
    done
    printf '\n### Laravel / PHP (allowlisted values only)\n\n'
    local v
    v="$(docker exec "$app" php -r 'echo PHP_VERSION;' 2>/dev/null || echo UNKNOWN)"
    printf -- '- PHP: `%s`\n' "$v"
    v="$(docker exec "$app" php artisan --version --no-ansi 2>/dev/null || echo UNKNOWN)"
    printf -- '- Laravel: `%s`\n' "$v"
    v="$(docker exec "$app" php -r 'echo ini_get("opcache.validate_timestamps");' 2>/dev/null || echo UNKNOWN)"
    printf -- '- CLI OPcache validate timestamps: `%s` (not evidence of FPM setting)\n' "$v"
    printf '\n### Code origin check\n\n'
    printf 'Image vs bind-mounted application code must be verified from mounts above. Host Git HEAD alone does not prove running code revision.\n'
  fi
  printf '\n## Compose overlay filenames (names only)\n\n'
  local found=0 f
  for f in "$PROJECT_DIR"/compose*.yaml "$PROJECT_DIR"/compose*.yml; do
    [[ -f "$f" ]] || continue
    printf -- '- `%s`\n' "$(basename "$f")"
    found=1
  done
  ((found)) || printf 'No compose*.yaml/yml found.\n'
  printf '\n## Revalidation triggers\n\n'
  printf -- '- After Git pull, image rebuild, container recreate, mount/Compose overlay change, PHP-FPM reload or environment/config change.\n'
  printf -- '- Incident-specific checks: exact URL, HTTP response, Laravel exception and relevant service logs. Do not store raw logs or secrets in this profile.\n'
  printf -- '- Any UNKNOWN/APP_NOT_RUNNING/NO_CONTAINERS_FOUND above is an incomplete diagnostic, not PASS.\n'
}
if [[ -n "$OUTPUT" ]]; then
  tmp="$(mktemp "$(dirname "$OUTPUT")/.production-profile.XXXXXX")"
  trap 'rm -f -- "$tmp"' EXIT
  chmod 600 "$tmp"
  render > "$tmp"
  mv -n -- "$tmp" "$OUTPUT"
  trap - EXIT
  printf 'Report created: %s\n' "$OUTPUT"
else
  render
fi
