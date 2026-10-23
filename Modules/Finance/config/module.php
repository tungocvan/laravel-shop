<?php

return [
    'name' => 'Finance',
    'type' => 'domain',
    'enabled' => false,
    'default_enabled' => false,
    'depends' => ['Admin', 'Auth', 'Role', 'Shared'],
    'permissions_required' => true,
    'permissions' => [
        'finance.view',
        'finance.account.manage',
        'finance.voucher.create',
        'finance.voucher.post',
        'finance.voucher.reverse',
        'finance.voucher.force-delete',
        'finance.report.view',
    ],
    'tables' => [
        'finance_accounts',
        'finance_source_documents',
        'finance_vouchers',
        'finance_allocations',
        'finance_ledger_entries',
    ],
];
