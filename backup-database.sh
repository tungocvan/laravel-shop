#!/bin/bash
param1="${1:-storage/app/backups/db_full.sql}"

echo "Đang sao lưu cơ sở dữ liệu vào file: $param1"
echo "Lưu ý: Hành động này sẽ tạo một bản sao lưu của toàn bộ cơ sở dữ liệu."

php artisan db:mysql backup $param1
