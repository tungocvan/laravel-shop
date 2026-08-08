#!/bin/bash
param1="$1"
if [ -z "$param1" ]; then
  echo "Vui long nhap ten table  can xoa"
  echo "Cau lenh xem cac table co trong database"
  echo "php artisan db:mysql query 'show tables'"
  exit 1
fi
php artisan clean:table $param1
php artisan migrate 