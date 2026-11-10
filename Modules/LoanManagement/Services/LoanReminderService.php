<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanReminderService
{
    public const OFFSETS = [7, 5, 2, 0];

    public function save(int $contractId, array $data): void
    {
        $contract = DB::table('loan_contracts')->where('id', $contractId)->first();
        abort_unless($contract, 404);
        if ($contract->status === 'cancelled') {
            throw ValidationException::withMessages(['contract' => 'Không thiết lập nhắc hẹn cho hợp đồng đã hủy.']);
        }
        $days = array_values(array_unique(array_map('intval', $data['days_before'] ?? [])));
        if ($data['enabled'] && $days === []) {
            throw ValidationException::withMessages(['days_before' => 'Chọn ít nhất một mốc nhắc hẹn.']);
        }
        if (array_diff($days, self::OFFSETS)) {
            throw ValidationException::withMessages(['days_before' => 'Mốc nhắc hẹn không hợp lệ.']);
        }
        rsort($days);
        DB::table('loan_reminder_settings')->updateOrInsert(
            ['loan_contract_id' => $contractId],
            ['enabled' => (bool) $data['enabled'], 'recipient_email' => $data['recipient_email'],
                'days_before' => json_encode($days), 'updated_at' => now()]
        );
    }

    public function settingsForContracts(array $ids): array
    {
        if ($ids === []) return [];
        return DB::table('loan_reminder_settings')->whereIn('loan_contract_id', $ids)->get()->keyBy('loan_contract_id')->all();
    }

    /** Reserves a single notification per schedule, offset and due-date snapshot. */
    public function reserveDueReminders(\Carbon\CarbonImmutable $today): int
    {
        $count = 0;
        $settings = DB::table('loan_reminder_settings as r')
            ->join('loan_contracts as c', 'c.id', '=', 'r.loan_contract_id')
            ->where('r.enabled', true)->where('c.status', 'active')
            ->select('r.*')->get();
        foreach ($settings as $setting) {
            $offsets = json_decode($setting->days_before, true);
            if (!is_array($offsets)) continue;
            foreach ($offsets as $offset) {
                if (!in_array($offset, self::OFFSETS, true)) continue;
                $due = $today->addDays($offset)->toDateString();
                $schedules = DB::table('loan_repayment_schedules')
                    ->where('loan_contract_id', $setting->loan_contract_id)
                    ->whereDate('due_at', $due)->get();
                foreach ($schedules as $schedule) {
                    $paid = DB::table('loan_payment_allocations')
                        ->where('loan_repayment_schedule_id', $schedule->id)
                        ->selectRaw('COALESCE(SUM(principal_allocated),0) as principal, COALESCE(SUM(interest_allocated),0) as interest, COALESCE(SUM(fees_allocated),0) as fees')
                        ->first();
                    $principal = bcsub((string) $schedule->principal_due, (string) $paid->principal, 2);
                    $interest = bcsub((string) $schedule->interest_due, (string) $paid->interest, 2);
                    $fees = bcsub((string) $schedule->fees_due, (string) $paid->fees, 2);
                    $total = bcadd(bcadd($principal, $interest, 2), $fees, 2);
                    if (bccomp($total, '0', 2) <= 0) continue;
                    $inserted = DB::table('loan_reminder_deliveries')->insertOrIgnore([
                        'loan_repayment_schedule_id' => $schedule->id,
                        'days_before' => $offset,
                        'due_at_snapshot' => $due,
                        'recipient_email' => $setting->recipient_email,
                        'status' => 'reserved', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($inserted) {
                        $delivery = DB::table('loan_reminder_deliveries')
                            ->where('loan_repayment_schedule_id', $schedule->id)
                            ->where('days_before', $offset)->where('due_at_snapshot', $due)->first();
                        \Modules\LoanManagement\Jobs\SendLoanReminder::dispatch($delivery->id)->afterCommit();
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
}
