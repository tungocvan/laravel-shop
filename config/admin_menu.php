<?php

return [
    [
        'label' => 'Dashboard',
        'icon' => 'fas fa-home',
        'route' => 'admin.dashboard',
        'permission' => 'view dashboard',
    ],
    [
        'label' => 'Người dùng',
        'icon' => 'fas fa-users',
        'permission' => 'manage users',
        'children' => [
            [
                'label' => 'Danh sách',
                'icon' => 'fas fa-list',
                'route' => 'users.index',
                'permission' => 'users-list',
            ],
            [
                'label' => 'Phân Quyền',
                'icon' => 'fas fa-plus',
                'route' => 'role.index',
                'permission' => 'role-list',
            ],
        ],
    ],
];
