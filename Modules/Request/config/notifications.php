<?php

return [
    'channels' => ['database', 'email'],
    'queue' => 'request-notifications',
    'outbox_queue' => 'request-outbox',
    'outbox_batch_size' => 50,
    'outbox_max_attempts' => 5,
    'outbox_lease_seconds' => 120,
    'outbox_backoff_seconds' => [60, 300, 900, 3600],
    'delivery_max_attempts' => 5,
    'delivery_lease_seconds' => 300,
];
