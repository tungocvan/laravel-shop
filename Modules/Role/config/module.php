<?php

return [
    'name' => 'Role',
    'type' => 'shell',
    'enabled' => true,
    'depends' => ['User'],
    'permissions' => [
        'view_role',
        'create_role',
        'edit_role',
        'delete_role',
        'import_role',
        'export_role',
    ],
];
