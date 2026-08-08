 1- Câu lệnh tách file migrations nhiều schema => thành nhiều file migrations , mỗi file chứa 1 schema
    php artisan split:migrations 0000_00_00_000000_merged_schema.php // file này nằm trong database/migrations/0000_00_00_000000_merged_schema.php
    sau khi thực hiện nó sẻ di chuyển file 0000_00_00_000000_merged_schema.php và thư mục _backup

 2- gộp tất cả các file migrations bao gồm các file migrations  ở module thành 1 file migrations chứa tất cả các schema.
php artisan  migrate:merge-schema
