<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LoanDataCleanService
{
    // Only tables owned by LoanManagement. Never truncate, disable FKs, or touch shared tables.
    private const DELETE_ORDER = [
        'loan_reminder_deliveries',
        'loan_schedule_adjustments',
        'loan_payment_allocations',
        'loan_reminder_recipients',
        'loan_reminder_settings',
        'loan_insurance_premium_schedules',
        'loan_insurance_policies',
        'loan_payments',
        'loan_repayment_schedules',
        'loan_disbursements',
        'loan_contracts',
        'loan_lenders',
    ];

    private function assertSafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Loan data cleaning is restricted to local/testing.');
        }
    }

    public function preview(): array
    {
        $this->assertSafeEnvironment();

        $counts = [];
        foreach (self::DELETE_ORDER as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * Permanently remove all LoanManagement-owned data, including real records.
     * FK constraints stay enabled. Any unexpected external reference causes rollback.
     */
    public function deleteAll(): void
    {
        $this->assertSafeEnvironment();

        DB::transaction(function (): void {
            // Serialize against concurrent changes to parent records where supported.
            DB::table('loan_lenders')->lockForUpdate()->get(['id']);
            DB::table('loan_contracts')->lockForUpdate()->get(['id']);

            foreach (self::DELETE_ORDER as $table) {
                DB::table($table)->delete();
            }
        }, 3);
    }
}
