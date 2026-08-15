<?php

namespace Modules\Invoices\Console\Commands;

use Illuminate\Console\Command;
use Modules\Invoices\Services\AutomaticInvoiceBackupService;

class BackupInvoiceFilesCommand extends Command
{
    protected $signature = 'invoices:backup-files {--email= : Override backup recipient} {--force : Run even when automatic backup is disabled}';
    protected $description = 'Backup new synchronized invoice files to email and record run history';

    public function handle(AutomaticInvoiceBackupService $service): int
    {
        if (! (bool) config('invoices.backup.automatic_enabled', false) && ! $this->option('force')) {
            $this->components->info('Automatic invoice backup is disabled.');
            return self::SUCCESS;
        }

        try {
            $run = $service->run($this->option('email') ?: null, 'automatic');
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());
            return self::FAILURE;
        }

        if ($run->status === 'skipped') {
            $this->components->info($run->message);
            return self::SUCCESS;
        }

        $this->components->info("Backup completed: {$run->files_count} files, {$run->emails_sent} emails.");
        return self::SUCCESS;
    }
}
