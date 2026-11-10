<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanReminderService
{
    public const OFFSETS = [7, 5, 2, 0];

    public const MAX_RECIPIENTS = 10;

    public function save(int $contractId, array $data): void
    {
        DB::transaction(function () use ($contractId, $data): void {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
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

            $emails = array_values(array_unique(array_map(
                static fn ($email) => mb_strtolower(trim((string) $email)),
                $data['recipient_emails'] ?? []
            )));
            if (count($emails) > self::MAX_RECIPIENTS || in_array('', $emails, true) || ($data['enabled'] && $emails === [])) {
                throw ValidationException::withMessages(['recipient_emails' => 'Cần từ 1 đến 10 Email hợp lệ khi bật nhắc hẹn.']);
            }
            foreach ($emails as $email) {
                if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(['recipient_emails' => 'Email nhận nhắc hẹn không hợp lệ.']);
                }
            }
            // Keep legacy primary email for backward compatibility; recipients table is canonical.
            $primary = $emails[0] ?? (string) (DB::table('loan_reminder_settings')
                ->where('loan_contract_id', $contractId)->value('recipient_email') ?: '');
            if ($primary === '') {
                throw ValidationException::withMessages(['recipient_emails' => 'Cần ít nhất một Email để lưu cấu hình.']);
            }
            DB::table('loan_reminder_settings')->updateOrInsert(
                ['loan_contract_id' => $contractId],
                ['enabled' => (bool) $data['enabled'], 'recipient_email' => $primary,
                    'days_before' => json_encode($days), 'updated_at' => now()]
            );
            DB::table('loan_reminder_recipients')->where('loan_contract_id', $contractId)->delete();
            foreach ($emails as $email) {
                DB::table('loan_reminder_recipients')->insert([
                    'loan_contract_id' => $contractId, 'email' => $email,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function settingsForContracts(array $ids): array
    {
        if ($ids === []) return [];
        return DB::table('loan_reminder_settings')->whereIn('loan_contract_id', $ids)->get()->keyBy('loan_contract_id')->all();
    }

    public function recipientsForContracts(array $ids): array
    {
        if ($ids === []) return [];
        return DB::table('loan_reminder_recipients')->whereIn('loan_contract_id', $ids)
            ->orderBy('id')->get()->groupBy('loan_contract_id')
            ->map(static fn ($rows) => $rows->pluck('email')->all())->all();
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
            $recipients = DB::table('loan_reminder_recipients')
                ->where('loan_contract_id', $setting->loan_contract_id)->pluck('email')->all();
            if ($recipients === []) continue;
            foreach ($offsets as $offset) {
                if (!in_array($offset, self::OFFSETS, true)) continue;
                $due = $today->addDays($offset)->toDateString();
                $schedules = DB::table('loan_repayment_schedules')
                    ->where('loan_contract_id', $setting->loan_contract_id)
                    ->whereDate('due_at', $due)->get();
                foreach ($schedules as $schedule) {
                    if ($today->toDateString() > $schedule->due_at) continue;
                    $paid = DB::table('loan_payment_allocations')
                        ->where('loan_repayment_schedule_id', $schedule->id)
                        ->selectRaw('COALESCE(SUM(principal_allocated),0) as principal, COALESCE(SUM(interest_allocated),0) as interest, COALESCE(SUM(fees_allocated),0) as fees')
                        ->first();
                    $principal = bcsub((string) $schedule->principal_due, (string) $paid->principal, 2);
                    $interest = bcsub((string) $schedule->interest_due, (string) $paid->interest, 2);
                    $fees = bcsub((string) $schedule->fees_due, (string) $paid->fees, 2);
                    $total = bcadd(bcadd($principal, $interest, 2), $fees, 2);
                    if (bccomp($total, '0', 2) <= 0) continue;
                    foreach ($recipients as $email) {
                        $inserted = DB::table('loan_reminder_deliveries')->insertOrIgnore([
                            'loan_repayment_schedule_id' => $schedule->id,
                            'days_before' => $offset,
                            'due_at_snapshot' => $due,
                            'recipient_email' => $email,
                            'status' => 'reserved', 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        if (!$inserted) continue;
                        $deliveryId = DB::table('loan_reminder_deliveries')
                            ->where('loan_repayment_schedule_id', $schedule->id)
                            ->where('days_before', $offset)->where('due_at_snapshot', $due)
                            ->where('recipient_email', $email)->value('id');
                        \Modules\LoanManagement\Jobs\SendLoanReminder::dispatch($deliveryId)->afterCommit();
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
}
