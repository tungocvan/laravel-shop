<?php

return [
    'name' => 'System',
    'type' => 'shell',
    'enabled' => true,
    'depends' => ['Admin', 'Role'],
    'permissions' => [
        'system.manage',
        'system.settings.view',
        'system.settings.update',
        'system.env.view',
        'system.env.update',
        'system.modules.view',
        'system.modules.update',
        'system.commands.run',
        'database.view',
        'database.backup',
        'database.download',
        'database.restore',
        'database.destroy',
    ],
];
