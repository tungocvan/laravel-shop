#!/bin/bash

set -e  # nếu có lỗi -> dừng luôn (tránh deploy nửa chừng)

CURRENT_DIR=$(basename "$PWD")
DEFAULT_QUEUE_NAME="Queue-$CURRENT_DIR"
REQUEST_QUEUE_NAME="Request-Queue-$CURRENT_DIR"
SCHEDULER_NAME="Scheduler-$CURRENT_DIR"
PHP_BIN="${PHP_BIN:-$(command -v php)}"

if [ -z "$PHP_BIN" ]; then
    echo "❌ Không tìm thấy PHP trong PATH."
    exit 1
fi

if ! command -v pm2 >/dev/null 2>&1; then
    echo "❌ Không tìm thấy PM2 trong PATH."
    exit 1
fi

echo "🚀 Starting Laravel queue/scheduler setup..."

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
# 5. PM2 helpers
# ========================
restart_or_start_default_queue() {
    if pm2 describe "$DEFAULT_QUEUE_NAME" >/dev/null 2>&1; then
        echo "♻️ Restarting PM2 process: $DEFAULT_QUEUE_NAME"
        pm2 restart "$DEFAULT_QUEUE_NAME"
    else
        echo "🚀 Starting PM2 process: $DEFAULT_QUEUE_NAME"
        pm2 start "$PHP_BIN" \
            --name "$DEFAULT_QUEUE_NAME" \
            --cwd "$PWD" \
            --max-memory-restart 300M \
            -- artisan queue:work \
            --sleep=3 \
            --tries=3 \
            --timeout=60 \
            --queue=default
    fi
}

restart_or_start_request_queue() {
    if pm2 describe "$REQUEST_QUEUE_NAME" >/dev/null 2>&1; then
        echo "♻️ Restarting PM2 process: $REQUEST_QUEUE_NAME"
        pm2 restart "$REQUEST_QUEUE_NAME"
    else
        echo "🚀 Starting PM2 process: $REQUEST_QUEUE_NAME"
        pm2 start "$PHP_BIN" \
            --name "$REQUEST_QUEUE_NAME" \
            --cwd "$PWD" \
            --max-memory-restart 300M \
            -- artisan queue:work database \
            --queue=request-outbox,request-notifications,request-exports \
            --sleep=3 \
            --tries=5 \
            --timeout=120
    fi
}

restart_or_start_scheduler() {
    if pm2 describe "$SCHEDULER_NAME" >/dev/null 2>&1; then
        echo "♻️ Restarting PM2 process: $SCHEDULER_NAME"
        pm2 restart "$SCHEDULER_NAME"
    else
        echo "🚀 Starting PM2 process: $SCHEDULER_NAME"
        pm2 start "$PHP_BIN" \
            --name "$SCHEDULER_NAME" \
            --cwd "$PWD" \
            -- artisan schedule:work
    fi
}

# ========================
# 6. Ensure all Laravel PM2 processes
# ========================
restart_or_start_default_queue
restart_or_start_request_queue
restart_or_start_scheduler

# ========================
# 7. Save PM2 state
# ========================
echo "💾 Saving PM2 state..."
pm2 save

# ========================
# 8. Summary
# ========================
echo ""
echo "✅ Laravel queue/scheduler setup done!"
echo ""
echo "PM2 processes:"
echo "  - $DEFAULT_QUEUE_NAME        → queue: default"
echo "  - $REQUEST_QUEUE_NAME        → request-outbox, request-notifications, request-exports"
echo "  - $SCHEDULER_NAME            → artisan schedule:work"
echo ""
pm2 list

echo ""
echo "Câu lệnh quản lý PM2:"
echo "  pm2 list"
echo "  pm2 logs $DEFAULT_QUEUE_NAME"
echo "  pm2 logs $REQUEST_QUEUE_NAME"
echo "  pm2 logs $SCHEDULER_NAME"
echo "  pm2 restart $DEFAULT_QUEUE_NAME"
echo "  pm2 restart $REQUEST_QUEUE_NAME"
echo "  pm2 restart $SCHEDULER_NAME"
echo "  pm2 monit"
