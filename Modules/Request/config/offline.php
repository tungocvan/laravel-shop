<?php

return [
    'enabled' => true,
    'snapshot_ttl_hours' => 24,
    'draft_ttl_hours' => 168,
    'max_bytes_per_user' => 5242880,
    'forbidden_classifications' => [
        'confidential',
        'secret',
        'attachment',
        'binary',
        'computed_server_only',
    ],
];
