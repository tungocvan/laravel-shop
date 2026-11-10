<?php

namespace Modules\LoanManagement\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Forecast only: never posts a payment or disbursement.
 * Simple interest on the committed principal using the contract's day-count basis.
 */
final class LoanScheduleGenerator
{
    public function create(int $contractId, string $mode): int
    {
        if (!in_array($mode, ['interest_monthly', 'all_at_maturity'], true)) {
            throw ValidationException::withMessages(['repayment_mode' => 'Phương thức trả nợ không hợp lệ.']);
        }

        return DB::transaction(function () use ($contractId, $mode): int {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if ($contract->status !== 'draft') {
                throw ValidationException::withMessages(['contract' => 'Chỉ tạo lịch tự động cho hợp đồng nháp.']);
            }
            if (DB::table('loan_repayment_schedules')->where('loan_contract_id', $contractId)->exists()) {
                throw ValidationException::withMessages(['repayment_mode' => 'Hợp đồng đã có lịch trả nợ.']);
            }
            $from = CarbonImmutable::parse($contract->signed_at);
            $end = CarbonImmutable::parse($contract->matures_at);
            if (!$end->greaterThan($from)) {
                throw ValidationException::withMessages(['matures_at' => 'Ngày đáo hạn phải sau ngày ký để tạo lịch.']);
            }
            $periods = [];
            if ($mode === 'all_at_maturity') {
                $periods[] = $end;
            } else {
                $anchor = $from;
                for ($month = 1; $month <= 120; $month++) {
                    $next = $anchor->addMonthsNoOverflow($month);
                    if ($next->greaterThanOrEqualTo($end)) {
                        $periods[] = $end;
                        break;
                    }
                    $periods[] = $next;
                }
                if ($periods === [] || !$periods[count($periods) - 1]->equalTo($end)) {
                    throw ValidationException::withMessages(['matures_at' => 'Lịch tự động hỗ trợ tối đa 120 kỳ/tháng.']);
                }
            }
            $calculator = app(LoanInterestCalculator::class);
            $start = $from;
            $rows = [];
            foreach ($periods as $index => $due) {
                $isLast = $index === count($periods) - 1;
                $rows[] = [
                    'loan_contract_id' => $contractId,
                    'installment' => $index + 1,
                    'due_at' => $due->toDateString(),
                    'principal_due' => $isLast ? $contract->committed_amount : '0.00',
                    'interest_due' => $calculator->calculate((string) $contract->committed_amount, (string) $contract->annual_rate_percent, $start, $due, (int) $contract->day_count_basis),
                    'fees_due' => '0.00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $start = $due;
            }
            DB::table('loan_repayment_schedules')->insert($rows);
            return count($rows);
        }, 3);
    }
}
