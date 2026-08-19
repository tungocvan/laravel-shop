<?php

namespace Modules\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\DatabaseService;
use Throwable;

class UploadDatabaseBackupToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 360;

    public function __construct(
        public readonly string $fileName,
        public readonly ?int $actorId = null,
    ) {}

    public function handle(DatabaseService $database, GoogleDriveConnectionService $drive): void
    {
        $drive->markBackupProcessing($this->fileName);

        $path = $database->getDownloadPath($this->fileName);
        if ($path === null) {
            $drive->markBackupFailed($this->fileName, 'File backup local không còn tồn tại.');
            Log::warning('Google Drive backup upload skipped because local file is missing.', [
                'backup' => $this->fileName,
                'actor_id' => $this->actorId,
            ]);
            return;
        }

        try {
            $result = $drive->uploadBackup($path, $this->fileName);

            Log::notice('Database backup uploaded to Google Drive.', [
                'backup' => $this->fileName,
                'drive_file_id' => $result['id'] ?? null,
                'actor_id' => $this->actorId,
            ]);
        } catch (Throwable $e) {
            $drive->markBackupFailed($this->fileName, $e->getMessage());
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        try {
            app(GoogleDriveConnectionService::class)->markBackupFailed(
                $this->fileName,
                $exception?->getMessage() ?: 'Upload Google Drive thất bại sau các lần thử lại.'
            );
        } catch (Throwable $statusException) {
            Log::error('Unable to persist failed Google Drive backup status.', [
                'backup' => $this->fileName,
                'error' => $statusException->getMessage(),
            ]);
        }
    }
}
