<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Jobs\SendDatabaseBackupEmail;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\DatabaseService;
use Throwable;

class BackupManager extends Component
{
    use AuthorizesSystemActions, WithFileUploads;

    private const RECENT_BACKUP_LIMIT = 50;

    public $sqlFile;
    public string $googleDriveUrl = '';
    public bool $showEmailModal = false;
    public string $emailBackupFile = '';
    public string $backupEmail = '';

    #[On('backup-updated')]
    public function refresh(): void
    {
        // Event-driven re-render only.
    }

    public function render(DatabaseService $service)
    {
        $allBackups = $service->getAllBackupFiles();
        $backups = array_slice($allBackups, 0, self::RECENT_BACKUP_LIMIT);

        return view('System::livewire.database.backup-manager', [
            'backups' => $backups,
            'backupHistoryLimit' => self::RECENT_BACKUP_LIMIT,
            'backupHistoryTruncated' => count($allBackups) > self::RECENT_BACKUP_LIMIT,
            'backupDirectories' => [
                'storage/app/private/backups',
                'storage/app/backups (thư mục cũ)',
            ],
        ]);
    }

    public function restoreBackup(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');

        try {
            if ($service->restoreFromFile($fileName)) {
                $this->notify('success', 'Khôi phục dữ liệu thành công!');
            }
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup restore failed.', $e, ['backup' => $fileName]);
            $this->notify('error', 'Khôi phục dữ liệu thất bại. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function deleteBackup(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $service->deleteBackup($fileName);
            session()->flash('success', "Đã xóa backup {$fileName}.");
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup delete failed.', $e, ['backup' => $fileName]);
            $this->notify('error', 'Không thể xóa file backup. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function uploadSql(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');

        $validated = $this->validate([
            'sqlFile' => ['required', 'file', 'max:20480'],
        ], [
            'sqlFile.required' => 'Vui lòng chọn file SQL.',
            'sqlFile.max' => 'File upload trực tiếp không được vượt quá 20 MB.',
        ]);

        try {
            $name = $service->importBackupFile(
                $validated['sqlFile']->getRealPath(),
                $validated['sqlFile']->getClientOriginalName(),
            );
            $this->reset('sqlFile');
            session()->flash('success', "Đã tải lên {$name}. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup upload failed.', $e, [
                'original_name' => $validated['sqlFile']->getClientOriginalName(),
            ]);
            $this->addError('sqlFile', 'Không thể nhập file backup. Vui lòng kiểm tra file SQL và log hệ thống.');
        }
    }

    public function importFromGoogleDrive(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        $this->validate([
            'googleDriveUrl' => ['required', 'url', 'max:2048'],
        ]);

        if (! preg_match('~(?:/file/d/|[?&]id=)([A-Za-z0-9_-]{10,})~', $this->googleDriveUrl, $matches)) {
            $this->addError('googleDriveUrl', 'Link Google Drive không hợp lệ. Hãy dùng link chia sẻ của một file SQL.');
            return;
        }

        $temporaryPath = tempnam(storage_path('framework'), 'drive-sql-');

        if ($temporaryPath === false) {
            $this->addError('googleDriveUrl', 'Không thể tạo file tạm để tải backup.');
            return;
        }

        try {
            $response = Http::withOptions(['sink' => $temporaryPath])
                ->connectTimeout(15)
                ->timeout(300)
                ->get('https://drive.usercontent.google.com/download', [
                    'id' => $matches[1],
                    'export' => 'download',
                    'confirm' => 't',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Google Drive download failed with HTTP '.$response->status());
            }

            $name = $service->importBackupFile($temporaryPath, 'google-drive-'.$matches[1].'.sql');
            $this->googleDriveUrl = '';
            session()->flash('success', "Đã tải {$name} từ Google Drive. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive backup import failed.', $e, ['drive_file_id' => $matches[1]]);
            $this->addError('googleDriveUrl', 'Không thể tải hoặc nhập backup từ Google Drive. Vui lòng kiểm tra quyền chia sẻ và log hệ thống.');
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function openEmailModal(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.download');

        $path = $service->getDownloadPath($fileName);

        if ($path === null) {
            $this->notify('error', 'File backup không tồn tại.');
            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->notify('error', 'Chỉ gửi được file backup có dung lượng tối đa 10MB.');
            return;
        }

        $this->emailBackupFile = $fileName;
        $this->backupEmail = (string) (auth('admin')->user()?->email ?? '');
        $this->resetErrorBag('backupEmail');
        $this->showEmailModal = true;
    }

    public function sendBackupEmail(DatabaseService $service): void
    {
        $this->authorizePermission('database.download');

        $validated = $this->validate([
            'emailBackupFile' => ['required', 'string', 'max:255'],
            'backupEmail' => ['required', 'email:rfc', 'max:255'],
        ], [
            'backupEmail.required' => 'Vui lòng nhập email nhận backup.',
            'backupEmail.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $path = $service->getDownloadPath($validated['emailBackupFile']);

        if ($path === null) {
            $this->addError('emailBackupFile', 'File backup không còn tồn tại.');
            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->addError('emailBackupFile', 'File backup vượt quá giới hạn 10MB.');
            return;
        }

        SendDatabaseBackupEmail::dispatch($validated['emailBackupFile'], $validated['backupEmail']);

        Log::info('Database backup email delivery queued.', [
            'actor_id' => (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier(),
            'backup' => $validated['emailBackupFile'],
            'recipient' => $validated['backupEmail'],
        ]);

        $this->showEmailModal = false;
        $this->emailBackupFile = '';
        session()->flash('success', 'Đã đưa yêu cầu gửi backup vào hàng đợi email.');
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, content: $message, message: $message);
    }

    private function reportOperationError(string $message, Throwable $exception, array $context = []): void
    {
        Log::error($message, $context + [
            'actor_id' => (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier(),
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
