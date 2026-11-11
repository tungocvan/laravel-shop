<?php

namespace Modules\LoanManagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\LoanManagement\Services\LoanDataCleanService;
use Throwable;

final class CleanLoanData extends Command
{
    protected $signature = 'loans:data-clean {--all : Delete every LoanManagement-owned business record}';
    protected $description = 'Permanently delete all LoanManagement business data on local/testing only';

    public function handle(LoanDataCleanService $cleaner): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Only local/testing environments are allowed.');
            return self::FAILURE;
        }

        if (! $this->option('all')) {
            $this->error('Specify --all to request full LoanManagement data deletion.');
            return self::FAILURE;
        }

        try {
            $counts = $cleaner->preview();
            $this->warn('ALL LoanManagement business records will be permanently deleted, including REAL lenders, individuals, organizations, credit institutions, contracts, payments, insurance and reminders.');
            foreach ($counts as $table => $count) {
                $this->line("{$table}: {$count}");
            }

            if (! $this->input->isInteractive() || $this->ask('Type DELETE-ALL-LOANS to confirm irreversible deletion') !== 'DELETE-ALL-LOANS') {
                $this->warn('Cancelled; no data changed.');
                return self::FAILURE;
            }

            $cleaner->deleteAll();
            $this->info('LoanManagement business data deleted. No demo data was reseeded.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('LoanManagement data cleaning failed; no successful cleanup confirmed: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
