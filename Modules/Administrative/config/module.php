<?php

return [
    'name' => 'Administrative',
    'type' => 'domain',
    'enabled' => true,
    'seeders' => [
        'Modules\\Administrative\\database\\seeders\\DatabaseSeeder',
    ],
    'permissions' => [
        'administrative.dashboard.view',
        'administrative.procedure.view',
        'administrative.procedure.create',
        'administrative.procedure.update',
        'administrative.procedure.archive',
        'administrative.submission.view',
        'administrative.submission.process',
        'administrative.submission.edit',
        'administrative.submission.delete',
        'administrative.file.download',
        'administrative.history.view',
    ],
    'tables' => [
        'administrative_procedures',
        'administrative_submissions',
        'administrative_files',
        'administrative_status_histories',
    ],
];
