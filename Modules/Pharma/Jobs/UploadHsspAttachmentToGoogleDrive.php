<?php

namespace Modules\Pharma\Jobs;

use App\Dossiers\Models\DossierAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;

class UploadHsspAttachmentToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $attachmentId,
        public readonly string $directory,
        public readonly string $remoteFileName,
        public readonly bool $keepLocal,
    ) {
        $this->onQueue('pharma');
    }

    public function handle(GoogleDriveConnectionService $drive): void
    {
        $attachment = DossierAttachment::query()->findOrFail($this->attachmentId);
        $attachment->update(['sync_status' => 'processing']);
        $path = $attachment->path;
        if (! Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('File staging HSSP không còn tồn tại.');
        }

        $folders = array_values(array_filter(explode('/', trim($this->directory, '/'))));
        $drive->uploadApplicationFile(
            Storage::disk('local')->path($path),
            $folders,
            $this->remoteFileName,
            $attachment->mime_type ?: 'application/octet-stream',
        );

        $remotePath = 'Laravel-Backup/'.trim($this->directory, '/').'/'.$this->remoteFileName;
        if (! $this->keepLocal) {
            Storage::disk('local')->delete($path);
        }
        $attachment->update([
            'path' => $this->keepLocal ? $path : $remotePath,
            'disk' => $this->keepLocal ? 'local' : 'google_drive',
            'remote_path' => $remotePath,
            'sync_status' => $this->keepLocal ? 'synced' : 'google_only',
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        DossierAttachment::query()->whereKey($this->attachmentId)->update(['sync_status' => 'sync_failed']);
    }
}
