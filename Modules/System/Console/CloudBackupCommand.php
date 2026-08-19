<?php

namespace Modules\System\Console;

use Illuminate\Console\Command;
use Modules\System\Jobs\UploadDatabaseBackupToGoogleDrive;
use Modules\System\Services\Cloud\CloudBackupAutomationService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\DatabaseService;
use Throwable;

class CloudBackupCommand extends Command
{
    protected $signature = 'system:cloud-backup {--force : Run immediately and ignore schedule time}';
    protected $description = 'Create a full database backup and optionally upload it to Google Drive.';

    public function handle(DatabaseService $database, GoogleDriveConnectionService $drive, CloudBackupAutomationService $automation): int
    {
        $config = $automation->config();
        if (! $this->option('force') && ! $automation->dueNow()) {
            return self::SUCCESS;
        }

        try {
            $before = array_column($database->getAllBackupFiles(), 'name');
            $database->backupFullDatabase();
            $created = collect($database->getAllBackupFiles())->first(
                fn (array $file): bool => ! in_array($file['name'], $before, true) && ($file['is_full'] ?? false)
            );

            if (! $created) {
                throw new \RuntimeException('Không xác định được file backup vừa tạo.');
            }

            if ($config['upload_drive'] && ($drive->status()['connected'] ?? false)) {
                $drive->markBackupQueued($created['name']);
                UploadDatabaseBackupToGoogleDrive::dispatch($created['name']);
            }

            $this->applyLocalRetention($database, $config['local_retention']);
            $automation->markRun('success', 'Đã tạo backup '.$created['name'].($config['upload_drive'] ? ' và đưa upload Drive vào queue.' : '.'));
            $this->info('Cloud backup completed: '.$created['name']);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $automation->markRun('failed', $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function applyLocalRetention(DatabaseService $database, int $keep): void
    {
        $fullBackups = array_values(array_filter($database->getAllBackupFiles(), static fn (array $file): bool => (bool) ($file['is_full'] ?? false)));
        foreach (array_slice($fullBackups, max(1, $keep)) as $file) {
            $database->deleteBackup($file['name']);
        }
    }
}
