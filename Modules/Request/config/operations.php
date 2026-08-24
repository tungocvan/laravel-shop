<?php

return [
    'page_size' => 25,
    'max_page_size' => 100,
    'retry_allowlist' => [
        'stage_activation',
        'outbox_dispatch',
        'export_generation',
    ],
];
