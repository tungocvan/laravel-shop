<?php

namespace Modules\LoanManagement\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\LoanManagement\Services\LoanReminderService;

final class DispatchLoanReminders extends Command
{
    protected $signature = 'loans:dispatch-reminders';
    protected $description = 'Queue enabled loan installment reminders due today (idempotent)';

    public function handle(LoanReminderService $service): int
    {
        if (!config('loan_reminders.dispatch_enabled', false)) {
            $this->components->info('Loan reminder dispatch disabled; no messages queued.');
            return self::SUCCESS;
        }
        $count = $service->reserveDueReminders(CarbonImmutable::today(config('app.timezone')));
        $this->components->info("Reserved {$count} loan reminder(s).");
        return self::SUCCESS;
    }
}
