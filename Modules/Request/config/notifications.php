<?php

return [
    'channels' => ['database', 'email'],
    'queue' => 'request-notifications',
    'outbox_queue' => 'request-outbox',
];
