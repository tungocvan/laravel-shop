<?php

return [
    'name' => 'LoanManagement',
    'type' => 'domain',
    'default_enabled' => false,
    'depends' => ['Auth', 'Role', 'Shared'],
    'permissions_required' => true,
    'permissions' => [
        'client.loans.access',
        'client.loans.overview.view',
        'client.loans.contracts.view',
        'client.loans.contracts.create',
        'client.loans.contracts.edit',
        'client.loans.payments.create',
        'client.loans.insurance.view',
        'client.loans.insurance.manage',
        'client.loans.reports.view',
    ],
    'tables' => [
        'loan_lenders',
        'loan_contracts',
        'loan_disbursements',
        'loan_repayment_schedules',
        'loan_payments',
        'loan_insurance_policies',
        'loan_insurance_premium_schedules',
    ],
];
