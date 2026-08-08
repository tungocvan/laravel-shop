<?php

return [
    'storage_disk' => env('ADMINISTRATIVE_STORAGE_DISK', 'local'),
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    'max_file_size_kb' => 10 * 1024,
    'max_files' => 5,
];
