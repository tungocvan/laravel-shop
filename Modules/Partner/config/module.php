<?php

return [
    'name' => 'Partner',
    'type' => 'domain',
    'enabled' => false,
    'permissions' => [
        'view_partner',
        'create_partner',
        'edit_partner',
        'delete_partner',
    ],
    'tables' => [
        'partners',
    ],
];
