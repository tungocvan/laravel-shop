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
# 4. Discover active general queues
# ========================
# Queue mặc định + queue do các Module đang bật khai báo trong config/module.php.
# Nhờ dùng ModuleRegistry runtime state, khi tắt Module thì queue của Module đó
# sẽ tự biến mất khỏi worker local ở lần chạy run-queue.sh kế tiếp.
# Request queues vẫn dùng worker riêng bên dưới vì có timeout/tries riêng.
echo "🔎 Discovering active module queues..."

QUEUE_PROFILE=""
if QUEUE_PROFILE=$("$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$modules = app(App\Modules\ModuleRegistry::class)->current();
$queues = ["default"];
$timeout = 60;
$tries = 3;

foreach ($modules as $module) {
    if (! (bool) ($module["enabled"] ?? false)) {
        continue;
    }

    $path = rtrim((string) ($module["path"] ?? ""), DIRECTORY_SEPARATOR);
    $configPath = $path !== "" ? $path.DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."module.php" : "";

    if ($configPath === "" || ! is_file($configPath)) {
        continue;
    }

    $config = require $configPath;
    if (! is_array($config)) {
        continue;
    }

    foreach ((array) ($config["queues"] ?? []) as $definition) {
        if (! is_array($definition)) {
            continue;
        }

        $name = trim((string) ($definition["name"] ?? ""));
        if ($name === "" || str_starts_with($name, "request-")) {
            continue;
        }

        $queues[] = $name;
        $timeout = max($timeout, (int) ($definition["timeout"] ?? 60));
        $tries = max($tries, (int) ($definition["tries"] ?? 3));
    }
}

$queues = array_values(array_unique($queues));
$defaultIndex = array_search("default", $queues, true);
if ($defaultIndex !== false) {
    unset($queues[$defaultIndex]);
}
sort($queues, SORT_STRING);
array_unshift($queues, "default");

echo implode(",", $queues)."|".$timeout."|".$tries;
' 2>/dev/null); then
    :
else
    echo "⚠️ Không đọc được Module Registry → fallback queue default."
fi

IFS='|' read -r GENERAL_QUEUES GENERAL_TIMEOUT GENERAL_TRIES <<< "$QUEUE_PROFILE"
GENERAL_QUEUES="${GENERAL_QUEUES:-default}"
GENERAL_TIMEOUT="${GENERAL_TIMEOUT:-60}"
GENERAL_TRIES="${GENERAL_TRIES:-3}"

echo "✅ General queues : $GENERAL_QUEUES"
echo "✅ Worker timeout : ${GENERAL_TIMEOUT}s"
echo "✅ Worker tries   : $GENERAL_TRIES"

# ========================
# 5. Restart queue (Laravel way)
# ========================
echo "♻️ Restart Laravel queue..."
php artisan queue:restart || true

# ========================
# 6. PM2 helpers
# ========================
restart_or_start_default_queue() {
    # Luôn recreate process này để queue list/timeout/tries mới thực sự được áp dụng.
    # `pm2 restart` đơn thuần giữ nguyên script args cũ và có thể làm queue mới bị Pending.
    if pm2 describe "$DEFAULT_QUEUE_NAME" >/dev/null 2>&1; then
        echo "♻️ Recreating PM2 process with current queue registry: $DEFAULT_QUEUE_NAME"
        pm2 delete "$DEFAULT_QUEUE_NAME" >/dev/null
    else
        echo "🚀 Starting PM2 process: $DEFAULT_QUEUE_NAME"
    fi

    pm2 start "$PHP_BIN" \
        --name "$DEFAULT_QUEUE_NAME" \
        --cwd "$PWD" \
        --max-memory-restart 300M \
        -- artisan queue:work \
        --sleep=3 \
        --tries="$GENERAL_TRIES" \
        --timeout="$GENERAL_TIMEOUT" \
        --queue="$GENERAL_QUEUES"
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
# 7. Ensure all Laravel PM2 processes
# ========================
restart_or_start_default_queue
restart_or_start_request_queue
restart_or_start_scheduler

# ========================
# 8. Save PM2 state
# ========================
echo "💾 Saving PM2 state..."
pm2 save

# ========================
# 9. Summary
# ========================
echo ""
echo "✅ Laravel queue/scheduler setup done!"
echo ""
echo "PM2 processes:"
echo "  - $DEFAULT_QUEUE_NAME        → queues: $GENERAL_QUEUES"
echo "  - $REQUEST_QUEUE_NAME        → request-outbox, request-notifications, request-exports"
echo "  - $SCHEDULER_NAME            → artisan schedule:work"
echo ""
pm2 list

echo ""
echo "Câu lệnh quản lý PM2:"
echo "  pm2 list"
echo "  pm2 show $DEFAULT_QUEUE_NAME"
echo "  pm2 logs $DEFAULT_QUEUE_NAME"
echo "  pm2 logs $REQUEST_QUEUE_NAME"
echo "  pm2 logs $SCHEDULER_NAME"
echo "  pm2 restart $DEFAULT_QUEUE_NAME"
echo "  pm2 restart $REQUEST_QUEUE_NAME"
echo "  pm2 restart $SCHEDULER_NAME"
echo "  pm2 monit"
