#!/bin/bash
php artisan module:migrate Admission --fresh
php artisan module:migrate Admission --refresh
php artisan db:seed --class="Modules\Admission\database\seeders\DatabaseSeeder"
php artisan optimize:clear


