#!/bin/bash

# Chạy từ thư mục hiện tại
cd /var/www/tnv
php artisan config:clear
# Đảm bảo dùng đúng PHP, ví dụ php8.3
php artisan queue:work --sleep=3 --tries=3 --timeout=60 --queue=default

