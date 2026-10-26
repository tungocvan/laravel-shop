<?php

return [
    // Leave unset to disable public-demo server writes. Never expose this value in Blade/JavaScript.
    'backup_code' => env('WAREHOUSE_DEMO_BACKUP_CODE'),
    'max_bytes' => 10 * 1024 * 1024,
    'max_datasets' => 100,
];
