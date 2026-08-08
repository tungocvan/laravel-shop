#!/bin/bash
# Thực hiện reset hàng đợi
php artisan queue:restart
php artisan queue:clear
php artisan optimize:clear
php artisan migrate

