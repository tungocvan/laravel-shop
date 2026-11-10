<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Explicit, auditable corrections to unpaid future installments.
 * Never changes actual payments or existing allocation records.
 */
final class LoanScheduleAdjustmentService
{
    public function update(int $contractId, int $scheduleId, array $data, ?int $actorId): void
    {
        DB::transaction(function () use ($contractId, $scheduleId, $data, $actorId): void {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            $schedule = DB::table('loan_repayment_schedules')
                ->where('loan_contract_id', $contractId)->where('id', $scheduleId)
                ->lockForUpdate()->first();
            abort_unless($schedule, 404);

            $today = now(config('app.timezone'))->toDateString();
            if (!in_array($contract->status, ['draft', 'active'], true) || $schedule->due_at <= $today) {
                throw ValidationException::withMessages(['schedule' => 'Chỉ sửa kỳ chưa đến hạn của hợp đồng nháp hoặc đang vay.']);
            }
            if (DB::table('loan_payment_allocations')->where('loan_repayment_schedule_id', $scheduleId)->exists()) {
                throw ValidationException::withMessages(['schedule' => 'Kỳ đã phát sinh thanh toán phân bổ, không thể sửa trực tiếp.']);
            }
            if ($data['due_at'] <= $today || $data['due_at'] < $contract->signed_at || $data['due_at'] > $contract->matures_at) {
                throw ValidationException::withMessages(['due_at' => 'Ngày mới phải ở tương lai và trong thời hạn hợp đồng.']);
            }

            $previous = DB::table('loan_repayment_schedules')->where('loan_contract_id', $contractId)
                ->where('installment', '<', $schedule->installment)->orderByDesc('installment')->first();
            $next = DB::table('loan_repayment_schedules')->where('loan_contract_id', $contractId)
                ->where('installment', '>', $schedule->installment)->orderBy('installment')->first();
            if (($previous && $data['due_at'] <= $previous->due_at) || ($next && $data['due_at'] >= $next->due_at)) {
                throw ValidationException::withMessages(['due_at' => 'Ngày đến hạn phải tăng dần theo thứ tự kỳ.']);
            }
            $principal = bcsub((string) DB::table('loan_repayment_schedules')
                ->where('loan_contract_id', $contractId)->sum('principal_due'), (string) $schedule->principal_due, 2);
            if (bccomp(bcadd($principal, (string) $data['principal_due'], 2), (string) $contract->committed_amount, 2) > 0) {
                throw ValidationException::withMessages(['principal_due' => 'Tổng gốc dự kiến vượt số tiền cam kết.']);
            }

            $old = [
                'due_at' => $schedule->due_at,
                'principal_due' => (string) $schedule->principal_due,
                'interest_due' => (string) $schedule->interest_due,
                'fees_due' => (string) $schedule->fees_due,
            ];
            $new = array_intersect_key($data, $old);
            DB::table('loan_repayment_schedules')->where('id', $scheduleId)
                ->update($new + ['updated_at' => now()]);
            DB::table('loan_schedule_adjustments')->insert([
                'loan_repayment_schedule_id' => $scheduleId,
                'actor_id' => $actorId,
                'before_json' => json_encode($old),
                'after_json' => json_encode($new),
                'created_at' => now(),
            ]);
            // Old reminder snapshots no longer match and will be skipped by SendLoanReminder.
            // Mark unsent reservations explicitly for operator visibility.
            DB::table('loan_reminder_deliveries')->where('loan_repayment_schedule_id', $scheduleId)
                ->whereIn('status', ['reserved', 'failed'])
                ->where('due_at_snapshot', '!=', $data['due_at'])
                ->update(['status' => 'skipped', 'updated_at' => now()]);
        }, 3);
    }
}
