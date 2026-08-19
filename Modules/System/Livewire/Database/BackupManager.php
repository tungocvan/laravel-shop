<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Jobs\SendDatabaseBackupEmail;
use Modules\System\Jobs\UploadDatabaseBackupToGoogleDrive;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
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
    public function refresh(): void {}

    public function render(DatabaseService $service, GoogleDriveConnectionService $drive)
    {
        $allBackups = $service->getAllBackupFiles();
        $backups = array_slice($allBackups, 0, self::RECENT_BACKUP_LIMIT);
        $driveStatus = $drive->status();

        foreach ($backups as &$backup) {
            $backup['google_drive'] = $drive->backupStatus($backup['name']);
        }
        unset($backup);

        return view('System::livewire.database.backup-manager', [
            'backups' => $backups,
            'driveStatus' => $driveStatus,
            'backupHistoryLimit' => self::RECENT_BACKUP_LIMIT,
            'backupHistoryTruncated' => count($allBackups) > self::RECENT_BACKUP_LIMIT,
            'backupDirectories' => ['storage/app/private/backups', 'storage/app/backups (thư mục cũ)'],
        ]);
    }

    public function backupAndUpload(DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('database.backup');
        if (! ($drive->status()['connected'] ?? false)) {
            $this->notify('error', 'Google Drive chưa được kết nối.');
            return;
        }

        try {
            $before = array_column($service->getAllBackupFiles(), 'name');
            $service->backupFullDatabase();
            $after = $service->getAllBackupFiles();
            $created = collect($after)->first(fn (array $file): bool => ! in_array($file['name'], $before, true) && ($file['is_full'] ?? false));
            if (! $created) throw new \RuntimeException('Đã backup local nhưng không xác định được file mới để upload.');

            UploadDatabaseBackupToGoogleDrive::dispatch($created['name'], auth('admin')->id());
            $this->notify('success', 'Backup local thành công. Đã đưa upload Google Drive vào hàng đợi.');
        } catch (Throwable $e) {
            $this->reportOperationError('Backup and Google Drive queue failed.', $e);
            $this->notify('error', 'Không thể hoàn tất thao tác. Nếu file local đã được tạo, file đó vẫn được giữ nguyên.');
        }
    }

    public function uploadToGoogleDrive(string $fileName, DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('database.backup');
        if (! ($drive->status()['connected'] ?? false)) {
            $this->notify('error', 'Google Drive chưa được kết nối.');
            return;
        }
        if ($service->getDownloadPath($fileName) === null) {
            $this->notify('error', 'File backup local không tồn tại.');
            return;
        }

        UploadDatabaseBackupToGoogleDrive::dispatch($fileName, auth('admin')->id());
        $this->notify('success', "Đã đưa {$fileName} vào hàng đợi upload Google Drive.");
    }

    public function restoreBackup(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        try {
            if ($service->restoreFromFile($fileName)) $this->notify('success', 'Khôi phục dữ liệu thành công!');
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
        $validated = $this->validate(['sqlFile' => ['required', 'file', 'max:20480']], [
            'sqlFile.required' => 'Vui lòng chọn file SQL.', 'sqlFile.max' => 'File upload trực tiếp không được vượt quá 20 MB.',
        ]);
        try {
            $name = $service->importBackupFile($validated['sqlFile']->getRealPath(), $validated['sqlFile']->getClientOriginalName());
            $this->reset('sqlFile');
            session()->flash('success', "Đã tải lên {$name}. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup upload failed.', $e, ['original_name' => $validated['sqlFile']->getClientOriginalName()]);
            $this->addError('sqlFile', 'Không thể nhập file backup. Vui lòng kiểm tra file SQL và log hệ thống.');
        }
    }

    public function importFromGoogleDrive(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        $this->validate(['googleDriveUrl' => ['required', 'url', 'max:2048']]);
        if (! preg_match('~(?:/file/d/|[?&]id=)([A-Za-z0-9_-]{10,})~', $this->googleDriveUrl, $matches)) {
            $this->addError('googleDriveUrl', 'Link Google Drive không hợp lệ. Hãy dùng link chia sẻ của một file SQL.'); return;
        }
        $temporaryPath = tempnam(storage_path('framework'), 'drive-sql-');
        if ($temporaryPath === false) { $this->addError('googleDriveUrl', 'Không thể tạo file tạm để tải backup.'); return; }
        try {
            $response = Http::withOptions(['sink' => $temporaryPath])->connectTimeout(15)->timeout(300)->get('https://drive.usercontent.google.com/download', ['id' => $matches[1], 'export' => 'download', 'confirm' => 't']);
            if (! $response->successful()) throw new \RuntimeException('Google Drive download failed with HTTP '.$response->status());
            $name = $service->importBackupFile($temporaryPath, 'google-drive-'.$matches[1].'.sql');
            $this->googleDriveUrl = '';
            session()->flash('success', "Đã tải {$name} từ Google Drive. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive backup import failed.', $e, ['drive_file_id' => $matches[1]]);
            $this->addError('googleDriveUrl', 'Không thể tải hoặc nhập backup từ Google Drive. Vui lòng kiểm tra quyền chia sẻ và log hệ thống.');
        } finally { @unlink($temporaryPath); }
    }

    public function openEmailModal(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.download');
        $path = $service->getDownloadPath($fileName);
        if ($path === null) { $this->notify('error', 'File backup không tồn tại.'); return; }
        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) { $this->notify('error', 'Chỉ gửi được file backup có dung lượng tối đa 10MB.'); return; }
        $this->emailBackupFile = $fileName;
        $this->backupEmail = (string) (auth('admin')->user()?->email ?? '');
        $this->resetErrorBag('backupEmail');
        $this->showEmailModal = true;
    }

    public function sendBackupEmail(DatabaseService $service): void
    {
        $this->authorizePermission('database.download');
        $validated = $this->validate(['emailBackupFile' => ['required','string','max:255'], 'backupEmail' => ['required','email:rfc','max:255']]);
        $path = $service->getDownloadPath($validated['emailBackupFile']);
        if ($path === null) { $this->addError('emailBackupFile', 'File backup không còn tồn tại.'); return; }
        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) { $this->addError('emailBackupFile', 'File backup vượt quá giới hạn 10MB.'); return; }
        SendDatabaseBackupEmail::dispatch($validated['emailBackupFile'], $validated['backupEmail']);
        Log::info('Database backup email delivery queued.', ['actor_id' => auth('admin')->id(), 'backup' => $validated['emailBackupFile'], 'recipient' => $validated['backupEmail']]);
        $this->showEmailModal = false; $this->emailBackupFile = '';
        session()->flash('success', 'Đã đưa yêu cầu gửi backup vào hàng đợi email.');
    }

    private function notify(string $type, string $message): void { $this->dispatch('notify', type: $type, content: $message, message: $message); }
    private function reportOperationError(string $message, Throwable $exception, array $context = []): void
    {
        Log::error($message, $context + ['actor_id' => auth('admin')->id(), 'exception' => $exception::class, 'error' => $exception->getMessage()]);
    }
}
