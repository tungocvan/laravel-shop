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
    'permissions_by_guard' => [
        'web' => [
            'client.partners.access',
            'client.partners.view',
            'client.partners.create',
            'client.partners.edit',
            'client.partners.delete',
            'client.partners.lookup',
            'client.partners.sync',
        ],
    ],
    'tables' => [
        'partners',
        'partner_source_references',
        'partner_sync_candidates',
    ],
];
