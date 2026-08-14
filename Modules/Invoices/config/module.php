<?php

return [
    'name' => 'Invoices',
    'type' => 'domain',
    'enabled' => true,
    'depends' => [],
    'tables' => [
        'invoices',
    ],
    'permissions' => [
        'invoices-list',
        'invoices-create',
        'invoices-export',
        'invoices-download',
        'invoices-configure',
    ],
];
