#!/bin/bash

set -e  # nếu có lỗi -> dừng luôn (tránh deploy nửa chừng)

CURRENT_DIR=$(basename "$PWD")
APP_NAME="$CURRENT_DIR-queue-laravel"

echo "🚀 Starting deploy..."

# ========================
# 1. Fix permission
# ========================
echo "🔧 Fixing permissions..."
chown -R www-data:www-data Modules/Admin/data || true
chown -R www-data:www-data storage/app || true

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

if pm2 describe $APP_NAME > /dev/null 2>&1; then
    echo "♻️ Restarting PM2 process..."
    pm2 restart $APP_NAME
else
    echo "🚀 Starting PM2 process..."
    pm2 start php \
        --name $APP_NAME \
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
    pm2 start queue-worker.sh	Khởi động \n
    hoặc chạy nền: ./pm2queue.sh \n
    pm2 stop laravel-queue	Dừng \n
    pm2 restart laravel-queue	Khởi động lại \n
    pm2 delete laravel-queue	Xóa tiến trình \n
    pm2 logs laravel-queue	Xem log \n
    pm2 flush laravel-queue // xóa các logs \n
    pm2 monit // xem log nodejs \n
"

