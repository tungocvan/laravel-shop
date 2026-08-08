<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        'null' => [
            'driver' => 'null',
        ],
        'socket.io' => [
            'driver' => 'socket.io',
            'host' => env('NODEJS_SERVER_URL', 'https://node.laravel.tk'),
        ],
    ],
];
