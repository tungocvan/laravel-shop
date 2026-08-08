#!/usr/bin/env bash

set -e

PROJECT_DIR="/opt/inafo-pharma/repo"

cd "$PROJECT_DIR"

DATE=$(date +"%Y%m%d-%H%M%S")
OUTDIR="$PROJECT_DIR/debug-report-$DATE"

mkdir -p "$OUTDIR"

echo "================================================="
echo " Laravel Debug Report"
echo "================================================="
echo

#########################################
echo "[1] Docker Services"
#########################################

docker compose config --services > "$OUTDIR/services.txt"

docker compose ps > "$OUTDIR/docker-ps.txt"

#########################################
echo "[2] Xóa Laravel log"
#########################################

docker compose exec app sh -lc '
rm -f storage/logs/*.log
mkdir -p storage/logs
'

#########################################
echo "[3] Clear cache"
#########################################

docker compose exec app php artisan optimize:clear \
> "$OUTDIR/optimize-clear.txt" 2>&1 || true

#########################################
echo "[4] Restart App"
#########################################

docker compose restart app \
> "$OUTDIR/restart.txt" 2>&1

sleep 5

#########################################
echo "[5] CURL Website"
#########################################

curl -i http://127.0.0.1:8081 \
> "$OUTDIR/curl.txt" 2>&1 || true

#########################################
echo "[6] Docker Logs"
#########################################

docker compose logs --tail=300 \
> "$OUTDIR/docker-compose.log" 2>&1 || true

#########################################
echo "[7] Laravel Log"
#########################################

docker compose exec app sh -lc '
if ls storage/logs/*.log >/dev/null 2>&1
then
    cat storage/logs/*.log
else
    echo "NO_LARAVEL_LOG_FOUND"
fi
' > "$OUTDIR/laravel.log" 2>&1

#########################################
echo "[8] PHP Info"
#########################################

docker compose exec app php artisan --version \
> "$OUTDIR/artisan-version.txt" 2>&1 || true

docker compose exec app php artisan about \
> "$OUTDIR/artisan-about.txt" 2>&1 || true

#########################################
echo "[9] Permission"
#########################################

docker compose exec app sh -lc '
ls -ld storage
ls -ld storage/logs
ls -ld bootstrap/cache
' > "$OUTDIR/permission.txt" 2>&1

#########################################
echo "[10] HTTP Header"
#########################################

curl -I http://127.0.0.1:8081 \
> "$OUTDIR/header.txt" 2>&1 || true

#########################################

echo
echo "================================================="
echo "DONE"
echo "================================================="
echo
echo "Report:"
echo
echo "$OUTDIR"
echo
ls -lh "$OUTDIR"
