<?php

namespace Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\System\Jobs\UploadDatabaseBackupToGoogleDrive;
use Modules\System\Services\Cloud\CloudBackupAutomationService;
use Modules\System\Services\Cloud\GoogleDriveBackupBrowserService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\DatabaseService;
use Throwable;

class CloudBackupCommand extends Command
{
    protected $signature = 'system:cloud-backup {--force : Run immediately and ignore schedule time}';

    protected $description = 'Create a full database backup and optionally upload it to Google Drive.';

    public function handle(DatabaseService $database, GoogleDriveConnectionService $drive, CloudBackupAutomationService $automation, GoogleDriveBackupBrowserService $browser): int
    {
        $config = $automation->config();

        if (! $this->option('force') && ! $automation->dueNow()) {
            return self::SUCCESS;
        }

        try {
            $created = $database->createFullDatabaseBackup();

            $queuedDrive = false;
            if ($config['upload_drive'] && ($drive->status()['connected'] ?? false)) {
                $drive->markBackupQueued($created['name']);
                UploadDatabaseBackupToGoogleDrive::dispatch($created['name']);
                $queuedDrive = true;
            }

            $this->applyLocalRetention($database, $config['local_retention']);

            if (($drive->status()['connected'] ?? false)) {
                $browser->applyRetention($config['drive_retention']);
            }

            $automation->markRun('success', $queuedDrive ? 'queued_drive' : 'local_only');
            $this->info('Cloud backup completed.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $automation->markRun('failed', 'operation_failed');
            Log::error('Cloud backup command failed.', ['exception' => $e::class]);
            $this->error('Cloud backup failed. Check the application log.');

            return self::FAILURE;
        }
    }

    private function applyLocalRetention(DatabaseService $database, int $keep): void
    {
        $fullBackups = array_values(array_filter($database->getAllBackupFiles(), static fn (array $file): bool => (bool) ($file['is_full'] ?? false)));

        foreach (array_slice($fullBackups, max(1, $keep)) as $file) {
            $database->deleteBackup($file['id']);
        }
    }
}
