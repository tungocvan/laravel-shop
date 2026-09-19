<?php

return [
    'name' => 'Pharma',
    'type' => 'domain',
    'enabled' => false,
    'depends' => [
        'Shared',
        'Partner',
    ],
    'snapshot' => [
        'related' => [
            [
                'name' => 'hssp_dossier_engine',
                'root' => [
                    'table' => 'dossiers',
                    'key' => 'id',
                    'where_column' => 'owner_type',
                    'where_value' => Modules\\Pharma\\Models\\MedicineProfile::class,
                    'owner_table' => 'medicine_profiles',
                    'owner_key' => 'id',
                    'owner_foreign_key' => 'owner_id',
                ],
                'references' => [
                    [
                        'source_table' => 'dossiers',
                        'source_column' => 'template_id',
                        'target_table' => 'dossier_templates',
                        'target_column' => 'id',
                    ],
                    [
                        'source_table' => 'dossier_templates',
                        'source_column' => 'id',
                        'target_table' => 'dossier_template_items',
                        'target_column' => 'template_id',
                    ],
                ],
                'children' => [
                    ['table' => 'dossier_items', 'foreign_key' => 'dossier_id'],
                    ['table' => 'dossier_attachments', 'foreign_key' => 'dossier_id'],
                ],
            ],
        ],
    ],
    'queues' => [
        [
            'name' => 'pharma',
            'timeout' => 600,
            'tries' => 3,
        ],
    ],
    'permissions' => [
        'view_pharma',
        'create_pharma',
        'edit_pharma',
        'delete_pharma',
        'view_pharma_allocations',
        'manage_pharma_allocations',
        'cancel_pharma_allocations',
        'view_pharma_contracts',
        'manage_pharma_contracts',
        'cancel_pharma_contracts',
        'view_pharma_official_facilities',
        'sync_pharma_official_facilities',
        'import_pharma_official_facilities',
        'resolve_pharma_official_facility_conflicts',
    ],
    'tables' => [
        'pharma_medicines',
        'pharma_drug_bid_awards',
        'pharma_supplier_trackings',
        'pharma_medicine_sources',
        'pharma_drug_bid_award_sources',
        'pharma_drug_bid_award_allocations',
        'pharma_drug_bid_award_contracts',
        'pharma_official_import_batches',
        'pharma_official_import_rows',
        'pharma_official_source_sync_batches',
        'pharma_official_source_facilities',
    ],
];
