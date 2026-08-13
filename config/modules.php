<?php

return [
    'log_discovery' => env('LOG_MODULE', false),

    'state' => [
        'driver' => 'file',
        'file' => storage_path('app/system/module-state.json'),
    ],
];
