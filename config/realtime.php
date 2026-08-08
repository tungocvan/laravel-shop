<?php

return [
    'allowed' => env('REALTIME_ALLOWED', true),
    'default_enabled' => env('REALTIME_ENABLED', true),
    'health_url' => env('SOCKET_HEALTH_URL'),
    'health_timeout' => (int) env('SOCKET_HEALTH_TIMEOUT', 2),
];
