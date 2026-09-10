<?php

return [
    'name' => 'Invoices',
    'type' => 'domain',
    'enabled' => true,
    'depends' => [],
    'tables' => [
        'invoices',
        'invoice_files',
        'invoice_backup_runs',
        'invoice_source_records',
    ],
    'permissions' => [
        'invoices-list',
        'invoices-create',
        'invoices-export',
        'invoices-download',
        'invoices-configure',
    ],
];
