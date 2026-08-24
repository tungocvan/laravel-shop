<?php

return [
    'disk' => env('REQUEST_FILES_DISK', env('FILESYSTEM_DISK', 'local')),
    'max_count' => 20,
    'max_count_per_field' => 5,
    'max_bytes' => 10 * 1024 * 1024,
    'max_bytes_per_request' => 50 * 1024 * 1024,
    'allowed_mimes' => [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'scan_driver' => env('REQUEST_FILE_SCAN_DRIVER', 'none'),
];
