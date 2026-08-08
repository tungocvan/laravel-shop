#!/bin/bash
param1="${1:-storage/app/backups/db_full.sql}"

echo "Đang khôi phục cơ sở dữ liệu từ file: $param1"
echo "Lưu ý: Hành động này sẽ xóa tất cả dữ liệu hiện tại trong cơ sở dữ liệu và thay thế bằng dữ liệu từ file sao lưu."

php artisan db:mysql restore $param1 --force
