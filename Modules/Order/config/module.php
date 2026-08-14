<?php

return [
    'name' => 'Order',
    'type' => 'domain',
    'enabled' => true,
    'depends' => [
        'User',
        'Product',
    ],
    'permissions' => [
        'orders-list',
        'orders-view',
        'orders-update-status',
        'orders-delete',
        'orders-print',
        'orders-export-pdf',
    ],
];
