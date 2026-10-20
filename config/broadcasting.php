<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        'null' => [
            'driver' => 'null',
        ],
        // Legacy boot-time broadcasting driver configuration. Do not replace
        // this host with a database read during config loading: custom driver
        // registration and callers must be verified before changing/removing it.
        'socket.io' => [
            'driver' => 'socket.io',
            'host' => env('NODEJS_SERVER_URL', 'https://node.laravel.tk'),
        ],
    ],
];
