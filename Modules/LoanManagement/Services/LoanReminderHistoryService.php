<?php

namespace Modules\LoanManagement\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class LoanReminderHistoryService
{
    /**
     * Read-only reminder history for the visible contracts.
     * 'reserved' means a delivery record exists, NOT proof a job remains in the queue.
     */
    public function forContracts(array $contractIds): array
    {
        if ($contractIds === []) {
            return ['deliveries' => [], 'upcoming' => []];
        }

        $settings = DB::table('loan_reminder_settings')
            ->whereIn('loan_contract_id', $contractIds)->get()->keyBy('loan_contract_id');
        $recipients = DB::table('loan_reminder_recipients')
            ->whereIn('loan_contract_id', $contractIds)->get()->groupBy('loan_contract_id');
        $schedules = DB::table('loan_repayment_schedules')
            ->whereIn('loan_contract_id', $contractIds)->orderBy('due_at')->get();
        $deliveries = DB::table('loan_reminder_deliveries as d')
            ->join('loan_repayment_schedules as s', 's.id', '=', 'd.loan_repayment_schedule_id')
            ->whereIn('s.loan_contract_id', $contractIds)
            ->select('s.loan_contract_id', 's.installment', 'd.id', 'd.days_before',
                'd.due_at_snapshot', 'd.recipient_email', 'd.status', 'd.sent_at',
                'd.created_at', 'd.updated_at')
            ->orderByDesc('d.created_at')->orderByDesc('d.id')->get()
            ->groupBy('loan_contract_id')
            ->map(static fn ($rows) => $rows->take(20)->values())->all();

        $today = CarbonImmutable::today(config('app.timezone'));
        $upcoming = [];
        foreach ($schedules as $schedule) {
            $setting = $settings[$schedule->loan_contract_id] ?? null;
            if (!$setting || !$setting->enabled || empty($recipients[$schedule->loan_contract_id])) {
                continue;
            }
            $offsets = json_decode($setting->days_before, true);
            if (!is_array($offsets)) continue;
            foreach ($offsets as $offset) {
                if (!in_array($offset, LoanReminderService::OFFSETS, true)) continue;
                $date = CarbonImmutable::parse($schedule->due_at)->subDays($offset);
                if ($date->lessThan($today)) continue;
                $upcoming[$schedule->loan_contract_id][] = [
                    'installment' => $schedule->installment,
                    'due_at' => $schedule->due_at,
                    'send_at' => $date->toDateString(),
                    'days_before' => $offset,
                    'recipient_count' => $recipients[$schedule->loan_contract_id]->count(),
                ];
            }
        }
        foreach ($upcoming as &$rows) {
            usort($rows, static fn (array $a, array $b) => strcmp($a['send_at'], $b['send_at']));
            $rows = array_slice($rows, 0, 5);
        }
        unset($rows);

        return ['deliveries' => $deliveries, 'upcoming' => $upcoming];
    }
}
