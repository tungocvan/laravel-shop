<?php

return [
    'name' => 'Pharma',
    'type' => 'domain',
    'enabled' => false,
    'depends' => [
        'Shared',
        'Partner',
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
    ],
];
