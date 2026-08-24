<?php

return [
    'create_title' => 'Export request register',
    'create_help' => 'The artifact contains only safe columns inside your currently authorized data scope.',
    'csv' => 'Export CSV',
    'xlsx' => 'Export XLSX',
    'invalid_format' => 'The requested export format is not supported.',
    'invalid_idempotency_key' => 'The export idempotency key is invalid.',
    'ready_message' => 'The export is ready to download.',
    'queued_message' => 'The export request was queued for processing.',
    'recent_title' => 'Recent exports',
    'recent_help' => 'Expired artifacts cannot be downloaded and are removed from private storage.',
    'no_exports' => 'You have not created an export yet.',
    'download' => 'Download',
    'rows' => ':count rows',
    'expires' => 'Expires :time',
    'statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'ready' => 'Ready',
        'failed' => 'Failed',
        'expired' => 'Expired',
    ],
];
