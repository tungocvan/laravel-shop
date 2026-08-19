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
        $path = $database->getDownloadPath($this->fileName);
        if ($path === null) {
            Log::warning('Google Drive backup upload skipped because local file is missing.', [
                'backup' => $this->fileName,
                'actor_id' => $this->actorId,
            ]);
            return;
        }

        $result = $drive->uploadBackup($path, $this->fileName);

        Log::notice('Database backup uploaded to Google Drive.', [
            'backup' => $this->fileName,
            'drive_file_id' => $result['id'] ?? null,
            'actor_id' => $this->actorId,
        ]);
    }
}
