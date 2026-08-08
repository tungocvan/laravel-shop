cd /opt/inafo-pharma/repo

echo "===== DIRECT HTTP ====="
curl -sS -D - http://127.0.0.1:8081 -o /tmp/inafo-response.html
head -40 /tmp/inafo-response.html

echo
echo "===== SERVICES ====="
docker compose config --services
docker compose ps

echo
echo "===== CONTAINER LOGS ====="
docker compose logs --tail=100

echo
echo "===== LARAVEL ABOUT ====="
docker compose exec app php artisan about || true

echo
echo "===== LARAVEL LOG ====="
docker compose exec app sh -lc '
LATEST=$(find storage/logs -maxdepth 1 -type f -name "*.log" | sort | tail -1)
if [ -n "$LATEST" ]; then
    echo "Log: $LATEST"
    tail -n 200 "$LATEST"
else
    echo "Không tìm thấy Laravel log"
fi
' || true

echo
echo "===== PERMISSIONS ====="
docker compose exec app sh -lc '
ls -ld storage bootstrap/cache
find storage -maxdepth 2 -printf "%M %u:%g %p\n" | head -40
' || true

echo
echo "===== APP KEY ====="
docker compose exec app sh -lc '
[ -n "$APP_KEY" ] && echo "APP_KEY=SET" || echo "APP_KEY=MISSING"
' || true

echo
echo "===== VITE ====="
docker compose exec app sh -lc '
test -f public/build/manifest.json \
&& echo "Vite manifest OK" \
|| echo "Vite manifest MISSING"
' || true

echo
echo "===== DATABASE ====="
docker compose exec app php artisan migrate:status || true
