#!/bin/bash

set -e  # nếu có lỗi -> dừng luôn (tránh deploy nửa chừng)

CURRENT_DIR=$(basename "$PWD")
APP_NAME="Queue-$CURRENT_DIR"

echo "🚀 Starting deploy..."

# ========================
# 1. Fix Laravel permissions
# ========================
echo "🔧 Fixing Laravel permissions..."

# Một số queue worker có thể đang chạy bằng root và tạo file/thư mục mà
# PHP-FPM (www-data) không traverse/read được. Chuẩn hóa ownership + mode
# trước khi restart worker để các file Excel/PDF/ZIP có thể tải từ web.
chown -R www-data:www-data storage bootstrap/cache

find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# Dữ liệu riêng của Admin nếu tồn tại.
if [ -d "Modules/Admin/data" ]; then
    chown -R www-data:www-data Modules/Admin/data
    find Modules/Admin/data -type d -exec chmod 775 {} \;
    find Modules/Admin/data -type f -exec chmod 664 {} \;
fi

# ========================
# 2. Clear cache
# ========================
echo "🧹 Clearing cache..."
php artisan optimize:clear

# ========================
# 3. Storage link
# ========================
echo "🔗 Checking storage link..."

if [ -L "public/storage" ]; then
    echo "✅ Storage link OK"
else
    echo "⚠️ Storage link missing or wrong → recreate"
    rm -rf public/storage
    php artisan storage:link
fi

# ========================
# 4. Restart queue (Laravel way)
# ========================
echo "♻️ Restart Laravel queue..."
php artisan queue:restart || true

# ========================
# 5. PM2 process
# ========================
echo "🔍 Checking PM2 process: $APP_NAME"

if pm2 describe "$APP_NAME" > /dev/null 2>&1; then
    echo "♻️ Restarting PM2 process..."
    pm2 restart "$APP_NAME"
else
    echo "🚀 Starting PM2 process..."
    pm2 start php \
        --name "$APP_NAME" \
        --max-memory-restart 300M \
        -- artisan queue:work --sleep=3 --tries=3 --timeout=60 --queue=default
fi

# ========================
# 6. Save PM2 state
# ========================
pm2 save

echo "✅ Deploy done!"

echo "
    Câu lệnh quản lý pm2 \n
    pm2 start queue-worker.sh\tKhởi động \n
    hoặc chạy nền: ./pm2queue.sh \n
    pm2 stop laravel-queue\tDừng \n
    pm2 restart laravel-queue\tKhởi động lại \n
    pm2 delete laravel-queue\tXóa tiến trình \n
    pm2 logs laravel-queue\tXem log \n
    pm2 flush laravel-queue // xóa các logs \n
    pm2 monit // xem log nodejs \n
"
