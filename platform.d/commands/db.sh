#!/usr/bin/env bash
set -Eeuo pipefail
source "$(dirname "$0")/../lib/common.sh"
ensure_project

show_help() {
cat <<'EOF'
USAGE
  ./platform-cli db <command> [arguments]

COMMANDS
  status
  shell
  export [file.sql]
  backup [file.sql.gz]
  import <file.sql|file.sql.gz>
  restore <file.sql|file.sql.gz>

EXAMPLES
  ./platform-cli db status
  ./platform-cli db shell
  ./platform-cli db export
  ./platform-cli db backup
  ./platform-cli db import /opt/nvh/db_nvh.sql
EOF
}

DB_DATABASE="$(read_env_value DB_DATABASE "$LARAVEL_ENV" || true)"
DB_USERNAME="$(read_env_value DB_USERNAME "$LARAVEL_ENV" || true)"
DB_PASSWORD="$(read_env_value DB_PASSWORD "$LARAVEL_ENV" || true)"
[[ -n "$DB_DATABASE" ]] || die "Thiếu DB_DATABASE"
[[ -n "$DB_USERNAME" ]] || die "Thiếu DB_USERNAME"

detect_client() {
  if compose_cmd exec -T db sh -lc 'command -v mariadb' >/dev/null 2>&1; then echo mariadb
  elif compose_cmd exec -T db sh -lc 'command -v mysql' >/dev/null 2>&1; then echo mysql
  else die "Container db không có mariadb/mysql client"
  fi
}
detect_dump() {
  if compose_cmd exec -T db sh -lc 'command -v mariadb-dump' >/dev/null 2>&1; then echo mariadb-dump
  elif compose_cmd exec -T db sh -lc 'command -v mysqldump' >/dev/null 2>&1; then echo mysqldump
  else die "Container db không có mariadb-dump/mysqldump"
  fi
}
db_exec() {
  local client="$1"; shift
  compose_cmd exec -T -e MYSQL_PWD="$DB_PASSWORD" db "$client" -u "$DB_USERNAME" "$@"
}

command="${1:-help}"
shift || true
case "$command" in
  help|-h|--help) show_help ;;
  status)
    CLIENT="$(detect_client)"
    echo "Database : $DB_DATABASE"
    echo "User     : $DB_USERNAME"
    TABLES="$(db_exec "$CLIENT" -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}';" "$DB_DATABASE")"
    SIZE="$(db_exec "$CLIENT" -Nse "SELECT ROUND(COALESCE(SUM(data_length+index_length),0)/1024/1024,2) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}';" "$DB_DATABASE")"
    echo "Tables   : $TABLES"
    echo "Size MB  : $SIZE"
    ;;
  shell)
    CLIENT="$(detect_client)"
    exec env PROJECT_DIR="$PROJECT_DIR" "$CENTRAL_PLATFORM_DIR/scripts/compose.sh" exec -e MYSQL_PWD="$DB_PASSWORD" db "$CLIENT" -u "$DB_USERNAME" "$DB_DATABASE"
    ;;
  export)
    DUMP="$(detect_dump)"
    OUT="${1:-$PROJECT_DIR/backup/database/${DB_DATABASE}_$(date +%Y%m%d_%H%M%S).sql}"
    [[ "$OUT" = /* ]] || OUT="$PROJECT_DIR/$OUT"
    mkdir -p "$(dirname "$OUT")"
    compose_cmd exec -T -e MYSQL_PWD="$DB_PASSWORD" db "$DUMP" -u "$DB_USERNAME" --single-transaction --quick --routines --triggers --events "$DB_DATABASE" > "$OUT"
    success "Đã export: $OUT"
    ;;
  backup)
    DUMP="$(detect_dump)"
    OUT="${1:-$PROJECT_DIR/backup/database/${DB_DATABASE}_$(date +%Y%m%d_%H%M%S).sql.gz}"
    [[ "$OUT" = /* ]] || OUT="$PROJECT_DIR/$OUT"
    mkdir -p "$(dirname "$OUT")"
    compose_cmd exec -T -e MYSQL_PWD="$DB_PASSWORD" db "$DUMP" -u "$DB_USERNAME" --single-transaction --quick --routines --triggers --events "$DB_DATABASE" | gzip -c > "$OUT"
    success "Đã backup: $OUT"
    ;;
  import|restore)
    FILE="${1:-}"
    [[ -n "$FILE" ]] || die "Thiếu file import"
    [[ "$FILE" = /* ]] || FILE="$PROJECT_DIR/$FILE"
    [[ -f "$FILE" ]] || die "Không thấy file: $FILE"
    echo "Database đích: $DB_DATABASE"
    echo "File import  : $FILE"
    confirm "Import sẽ thay đổi dữ liệu. Tiếp tục?" || die "Đã hủy"
    CLIENT="$(detect_client)"
    case "$FILE" in
      *.sql) db_exec "$CLIENT" "$DB_DATABASE" < "$FILE" ;;
      *.sql.gz|*.gz) gzip -dc "$FILE" | db_exec "$CLIENT" "$DB_DATABASE" ;;
      *) die "Chỉ hỗ trợ .sql hoặc .sql.gz" ;;
    esac
    success "Import thành công vào $DB_DATABASE"
    ;;
  *) error "DB command không hợp lệ: $command"; show_help; exit 1 ;;
esac
