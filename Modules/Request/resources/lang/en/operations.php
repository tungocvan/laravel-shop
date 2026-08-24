<?php

return [
    'title' => 'Request operations',
    'description' => 'Review retryable failures and run only allowlisted recovery actions.',
    'empty' => 'There are no retryable failures right now.',
    'kind' => 'Operation',
    'target' => 'Target',
    'error' => 'Error code',
    'attempts' => 'Attempts',
    'updated_at' => 'Updated',
    'retry' => 'Retry',
    'retry_started' => 'The safe retry request was submitted.',
    'allowlist_help' => 'Only allowlisted operations can be retried; arbitrary command execution is not supported.',
    'kinds' => [
        'stage_activation' => 'Approval stage activation',
        'outbox_dispatch' => 'Outbox dispatch',
        'export_generation' => 'Export generation',
    ],
];
