<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;

final class LoanContractHistoryService
{
    /**
     * Read-only records for the contracts already selected by the dashboard.
     * No derived installment settlement or interest accrual is inferred.
     */
    public function forContracts(array $contractIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $contractIds)));
        if ($ids === []) {
            return ['disbursements' => [], 'schedules' => [], 'payments' => [], 'totals' => []];
        }

        $definitions = [
            'disbursements' => ['loan_disbursements', 'disbursed_at'],
            'schedules' => ['loan_repayment_schedules', 'due_at'],
            'payments' => ['loan_payments', 'paid_at'],
        ];
        $result = [];
        foreach ($definitions as $key => [$table, $date]) {
            $result[$key] = DB::table($table)
                ->whereIn('loan_contract_id', $ids)
                // Installments follow their business sequence; other records remain chronological.
                ->orderBy($key === 'schedules' ? 'installment' : $date)
                ->orderBy('id')
                ->get()
                ->groupBy('loan_contract_id')
                ->all();
        }

        $result['totals'] = [];
        foreach ($ids as $id) {
            $principal = '0.00';
            $interest = '0.00';
            $fees = '0.00';
            foreach ($result['payments'][$id] ?? [] as $payment) {
                $principal = bcadd($principal, (string) $payment->principal_paid, 2);
                $interest = bcadd($interest, (string) $payment->interest_paid, 2);
                $fees = bcadd($fees, (string) $payment->fees_paid, 2);
            }
            $result['totals'][$id] = [
                'principal' => $principal,
                'interest' => $interest,
                'fees' => $fees,
                'paid' => bcadd(bcadd($principal, $interest, 2), $fees, 2),
            ];
        }

        return $result;
    }
}
