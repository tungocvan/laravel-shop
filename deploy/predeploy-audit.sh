#!/usr/bin/env bash
# Read-only production readiness audit for a Laravel deployment.
# It intentionally does not run migrations, build/start Docker, or expose secrets.

set -uo pipefail

PROJECT_DIR="$(pwd)"
OUTPUT_DIR=""

usage() {
    cat <<'EOF'
Usage: ./deploy/predeploy-audit.sh [--project-dir PATH] [--output-dir PATH]

Runs read-only checks and writes one timestamped log file. Environment values are
redacted. No Docker containers, databases, files, or application configuration are
changed.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --project-dir)
            PROJECT_DIR="$2"
            shift 2
            ;;
        --output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            printf 'Unknown option: %s\n' "$1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

if [[ ! -d "$PROJECT_DIR" ]]; then
    printf 'Project directory does not exist: %s\n' "$PROJECT_DIR" >&2
    exit 2
fi

PROJECT_DIR="$(cd "$PROJECT_DIR" && pwd -P)"
if [[ -z "$OUTPUT_DIR" ]]; then
    OUTPUT_DIR="$PROJECT_DIR/deploy-reports"
fi

mkdir -p "$OUTPUT_DIR"
OUTPUT_DIR="$(cd "$OUTPUT_DIR" && pwd -P)"
LOG_FILE="$OUTPUT_DIR/predeploy-audit-$(date -u +%Y%m%dT%H%M%SZ).log"

exec > >(tee "$LOG_FILE") 2>&1

section() {
    printf '\n===== %s =====\n' "$1"
}

run() {
    printf '\n$'
    printf ' %q' "$@"
    printf '\n'
    "$@"
    local status=$?
    if [[ $status -ne 0 ]]; then
        printf '[command exited %s]\n' "$status"
    fi
    return 0
}

command_path() {
    command -v "$1" 2>/dev/null || printf 'not found'
}

section 'Audit metadata'
printf 'UTC timestamp: %s\n' "$(date -u --iso-8601=seconds)"
printf 'Project directory: %s\n' "$PROJECT_DIR"
printf 'Log file: %s\n' "$LOG_FILE"
printf 'Effective user: %s (uid=%s)\n' "$(id -un)" "$(id -u)"

section 'Host operating system and capacity'
run uname -a
[[ -r /etc/os-release ]] && run cat /etc/os-release
run uptime
run free -h
run df -hT "$PROJECT_DIR"
run df -ih "$PROJECT_DIR"

section 'Network exposure and firewall'
if command -v ss >/dev/null 2>&1; then
    run ss -lntup
else
    printf 'ss is not installed.\n'
fi
if command -v ufw >/dev/null 2>&1; then
    run ufw status verbose
else
    printf 'ufw command is not available to this user.\n'
fi

section 'Host services'
if command -v systemctl >/dev/null 2>&1; then
    for service in nginx docker mariadb mysql postgresql redis-server; do
        printf '%-16s %s\n' "$service" "$(systemctl is-active "$service" 2>/dev/null || true)"
    done
fi
printf 'nginx: %s\n' "$(command_path nginx)"
if command -v nginx >/dev/null 2>&1; then
    run nginx -v
fi

section 'Docker runtime (read-only)'
printf 'docker: %s\n' "$(command_path docker)"
if command -v docker >/dev/null 2>&1; then
    run docker version --format '{{.Client.Version}}'
    run docker compose version
    run docker ps --all --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'
    run docker network ls
    run docker volume ls
fi

section 'Project source and Git state'
if command -v git >/dev/null 2>&1 && git -C "$PROJECT_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    run git -C "$PROJECT_DIR" remote -v
    run git -C "$PROJECT_DIR" branch --show-current
    run git -C "$PROJECT_DIR" log -1 --format='commit=%H%nauthor=%an%ndate=%cI%nsubject=%s'
    run git -C "$PROJECT_DIR" status --short
else
    printf 'No Git worktree detected.\n'
fi

section 'Deployment file inventory'
for file in Dockerfile compose.yml compose.yaml docker-compose.yml docker-compose.yaml .dockerignore; do
    if [[ -e "$PROJECT_DIR/$file" ]]; then
        printf 'present: %s\n' "$file"
    fi
done
find "$PROJECT_DIR" -maxdepth 3 -type f \( -name '*.conf' -o -name '*.ini' -o -name 'entrypoint.sh' -o -name 'supervisord.conf' \) -printf '%P\n' 2>/dev/null | sort

section 'Application manifests and generated assets'
for file in composer.json composer.lock package.json package-lock.json pnpm-lock.yaml yarn.lock artisan; do
    if [[ -e "$PROJECT_DIR/$file" ]]; then
        printf '%-24s %s bytes\n' "$file" "$(stat -c '%s' "$PROJECT_DIR/$file")"
    fi
done
if [[ -d "$PROJECT_DIR/public/build" ]]; then
    printf 'public/build: present (%s files)\n' "$(find "$PROJECT_DIR/public/build" -type f | wc -l)"
else
    printf 'public/build: absent\n'
fi

section 'Environment variable names (values redacted)'
for env_file in "$PROJECT_DIR/.env" "$PROJECT_DIR/.env.production" "$PROJECT_DIR/.env.example"; do
    if [[ -f "$env_file" ]]; then
        printf '[%s]\n' "${env_file#$PROJECT_DIR/}"
        sed -nE 's/^[[:space:]]*([A-Za-z_][A-Za-z0-9_]*)=.*/\1=***REDACTED***/p' "$env_file" | sort
    fi
done

section 'Persistent data inventory (names, paths, sizes only)'
for path in storage/app storage/logs storage/framework bootstrap/cache public/storage database; do
    full_path="$PROJECT_DIR/$path"
    if [[ -e "$full_path" ]]; then
        printf '\n[%s]\n' "$path"
        du -sh "$full_path" 2>/dev/null || true
        find "$full_path" -maxdepth 2 -mindepth 1 -printf '%y %s %p\n' 2>/dev/null | sed "s#${PROJECT_DIR}/##" | sort | head -n 200
    else
        printf '%s: absent\n' "$path"
    fi
done

section 'Potential database files and backup artifacts (metadata only)'
find "$PROJECT_DIR" -path "$PROJECT_DIR/vendor" -prune -o -path "$PROJECT_DIR/node_modules" -prune -o \( -iname '*.sqlite' -o -iname '*.sql' -o -iname '*.sql.gz' -o -iname '*.dump' \) -type f -printf '%s bytes %p\n' 2>/dev/null | sed "s#${PROJECT_DIR}/##" | sort

section 'Application process configuration clues'
grep -RIlE --exclude-dir=.git --exclude-dir=vendor --exclude-dir=node_modules \
    --include='*.php' --include='*.js' --include='*.sh' \
    'queue:work|schedule:work|Schedule::|withoutOverlapping|socket\.io|NODEJS_SERVER|libreoffice' \
    "$PROJECT_DIR/app" "$PROJECT_DIR/Modules" "$PROJECT_DIR/routes" "$PROJECT_DIR/bootstrap" "$PROJECT_DIR/socket" "$PROJECT_DIR/queue" 2>/dev/null \
    | sed "s#${PROJECT_DIR}/##" | sort | head -n 300 || true

section 'Audit result'
printf 'Completed. Review this log before sharing it; values from .env were redacted, but infrastructure metadata remains sensitive.\n'
printf 'No deployment action, migration, package installation, Docker build/up, or data mutation was performed.\n'
