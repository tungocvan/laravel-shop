<?php

namespace Modules\LoanManagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\LoanManagement\Database\Seeders\LoanManagementDemoSeeder;
use Modules\LoanManagement\Services\LoanDemoResetService;
use Throwable;

final class SeedLoanDemo extends Command
{
    protected $signature = 'loans:demo-seed {--delete : Delete the eight demo contracts and their dependent records before reseeding}';
    protected $description = 'Seed or explicitly reset the local/testing LoanManagement demo scenarios';

    public function handle(LoanDemoResetService $reset): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Only local/testing environments are allowed.');
            return self::FAILURE;
        }

        try {
            if (! $this->option('delete')) {
                app(LoanManagementDemoSeeder::class)->run();
                $this->info('LoanManagement demo seed completed (existing contracts preserved).');
                return self::SUCCESS;
            }

            $counts = $reset->preview();
            $this->warn('Only DEMO-LOAN-20261110-01 through 08 will be reset.');
            foreach ($counts as $table => $count) {
                $this->line("{$table}: {$count}");
            }
            $this->warn('This permanently removes ALL records attached to these demo contracts, including manually entered payments, reminders and audit history.');
            if (! $this->input->isInteractive() || $this->ask('Type RESET-DEMO to delete and recreate the demo dataset') !== 'RESET-DEMO') {
                $this->warn('Cancelled; no data changed.');
                return self::FAILURE;
            }

            $reset->resetAndSeed();
            $this->info('Demo reset complete: eight scenarios recreated; unrelated contracts preserved.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Demo seed/reset failed: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
