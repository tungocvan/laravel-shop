<?php

return [
    [
        'id' => 'queues',
        'label' => 'Queue Manager',
        'icon' => 'M4 6h16M4 12h16M4 18h16',
        'component' => 'system.settings.queue-manager',
        'enabled' => true,
    ],
    [
        'id' => 'mail',
        'label' => 'Email',
        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z',
        'component' => 'system.settings.mail-config',
        'enabled' => true,
    ],
    [
        'id' => 'database',
        'label' => 'Kết nối Database',
        'icon' => 'M4 7v10c0 2.21 3.58 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.58 4 8 4s8-1.79 8-4M4 7c0-2.21 3.58-4 8-4s8 1.79 8 4',
        'component' => 'system.settings.database-config',
        'enabled' => true,
    ],
    [
        'id' => 'artisan',
        'label' => 'Thực hiện Artisan',
        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'component' => 'system.settings.artisan-list',
        'enabled' => false,
    ],
    [
        'id' => 'sh',
        'label' => 'Thực hiện Sh Script',
        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'component' => 'system.settings.sh-script',
        'enabled' => false,
    ],
];
