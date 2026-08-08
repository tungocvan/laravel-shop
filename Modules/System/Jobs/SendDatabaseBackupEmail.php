<?php

namespace Modules\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\System\Services\DatabaseService;
use RuntimeException;

class SendDatabaseBackupEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $backupFile,
        public string $recipient,
    ) {}

    public function handle(DatabaseService $service): void
    {
        $path = $service->getDownloadPath($this->backupFile);

        if ($path === null || ! is_readable($path)) {
            throw new RuntimeException('File backup không tồn tại hoặc không thể đọc.');
        }

        if (filesize($path) > self::MAX_ATTACHMENT_BYTES) {
            throw new RuntimeException('File backup vượt quá giới hạn gửi email 10MB.');
        }

        Mail::raw(
            "File backup database {$this->backupFile} được gửi từ hệ thống vào ".now()->format('d/m/Y H:i:s').'.',
            function ($message) use ($path): void {
                $message
                    ->to($this->recipient)
                    ->subject('Database Backup - '.$this->backupFile)
                    ->attach($path, [
                        'as' => $this->backupFile,
                        'mime' => 'application/sql',
                    ]);
            },
        );
    }
}
