<?php

return [
    'disk' => env('EBOOK_DISK', 'local'),
    'root' => env('EBOOK_ROOT', 'ebooks'),
    'allowed_extensions' => ['md'],
    'upload_max_kb' => (int) env('EBOOK_UPLOAD_MAX_KB', 2048),
    'search' => [
        'max_documents' => (int) env('EBOOK_SEARCH_MAX_DOCUMENTS', 500),
        'max_file_kb' => (int) env('EBOOK_SEARCH_MAX_FILE_KB', 512),
        'max_total_kb' => (int) env('EBOOK_SEARCH_MAX_TOTAL_KB', 10240),
    ],
    'recent_limit' => (int) env('EBOOK_RECENT_LIMIT', 20),
];
