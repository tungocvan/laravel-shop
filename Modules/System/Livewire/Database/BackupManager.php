<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Jobs\SendDatabaseBackupEmail;
use Modules\System\Jobs\UploadDatabaseBackupToGoogleDrive;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Cloud\GoogleDriveBackupBrowserService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\DatabaseService;
use Throwable;

class BackupManager extends Component
{
    use AuthorizesSystemActions, WithFileUploads;

    private const RECENT_BACKUP_LIMIT = 50;

    public $sqlFile;

    public bool $showEmailModal = false;

    public string $emailBackupReference = '';

    public string $emailBackupName = '';

    public string $backupEmail = '';

    public bool $showDriveBackups = true;

    #[On('backup-updated')]
    public function refresh(): void
    {
        // Livewire re-renders the bounded local and remote backup catalogs.
    }

    public function render(
        DatabaseService $service,
        GoogleDriveConnectionService $drive,
        GoogleDriveBackupBrowserService $browser,
    ) {
        $user = auth('admin')->user() ?: auth()->user();
        $capabilities = [
            'backup' => (bool) $user?->can('database.backup'),
            'download' => (bool) $user?->can('database.download'),
            'restore' => (bool) $user?->can('database.restore'),
            'destroy' => (bool) $user?->can('database.destroy'),
        ];

        $allBackups = $service->getAllBackupFiles();
        $backups = array_slice($allBackups, 0, self::RECENT_BACKUP_LIMIT);
        $connectionStatus = $drive->status();
        $driveStatus = [
            'connected' => (bool) ($connectionStatus['connected'] ?? false),
            'folder_name' => (string) ($connectionStatus['folder_name'] ?? 'Laravel-Backup'),
        ];
        $driveCounts = ['queued' => 0, 'processing' => 0, 'uploaded' => 0, 'failed' => 0];

        foreach ($backups as &$backup) {
            $backup['google_drive'] = $drive->backupStatus($backup['name']);
            $state = (string) ($backup['google_drive']['status'] ?? '');

            if (isset($driveCounts[$state])) {
                $driveCounts[$state]++;
            }
        }
        unset($backup);

        $remoteBackups = [];
        $remoteBackupsUnavailable = false;
        $canBrowseRemote = $capabilities['download'] || $capabilities['destroy'];

        if ($driveStatus['connected'] && $this->showDriveBackups && $canBrowseRemote) {
            try {
                $remoteBackups = $browser->listBackups(100);
            } catch (Throwable $e) {
                $remoteBackupsUnavailable = true;
                Log::warning('Unable to list Google Drive backups.', ['exception' => $e::class]);
            }
        }

        return view('System::livewire.database.backup-manager', [
            'backups' => $backups,
            'driveStatus' => $driveStatus,
            'driveCounts' => $driveCounts,
            'remoteBackups' => $remoteBackups,
            'remoteBackupsUnavailable' => $remoteBackupsUnavailable,
            'canBrowseRemote' => $canBrowseRemote,
            'capabilities' => $capabilities,
            'backupHistoryLimit' => self::RECENT_BACKUP_LIMIT,
            'backupHistoryTruncated' => count($allBackups) > self::RECENT_BACKUP_LIMIT,
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
            $created = $service->createFullDatabaseBackup();
            $this->queueDriveUpload($created['name'], $drive);
            $this->notify('success', 'Backup local thành công. Đã đưa upload Google Drive vào hàng đợi.');
        } catch (Throwable $e) {
            $this->reportOperationError('Backup and Google Drive queue failed.', $e);
            $this->notify('error', 'Không thể hoàn tất thao tác. File backup chưa hoàn chỉnh sẽ không được công bố.');
        }
    }

    public function uploadToGoogleDrive(
        string $backupReference,
        DatabaseService $service,
        GoogleDriveConnectionService $drive,
    ): void {
        $this->authorizePermission('database.backup');

        if (! ($drive->status()['connected'] ?? false)) {
            $this->notify('error', 'Google Drive chưa được kết nối.');

            return;
        }

        $backup = $service->getBackupDescriptor($backupReference, ['sql']);

        if ($backup === null) {
            $this->notify('error', 'File backup local không tồn tại.');

            return;
        }

        $this->queueDriveUpload($backup['name'], $drive);
        $this->notify('success', 'Đã đưa backup vào hàng đợi upload Google Drive.');
    }

    public function retryGoogleDriveUpload(
        string $backupReference,
        DatabaseService $service,
        GoogleDriveConnectionService $drive,
    ): void {
        $this->uploadToGoogleDrive($backupReference, $service, $drive);
    }

    public function deleteRemoteBackup(string $reference, GoogleDriveBackupBrowserService $browser): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $browser->delete($reference);
            $this->notify('success', 'Đã xóa backup khỏi Google Drive.');
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive remote backup delete failed.', $e);
            $this->notify('error', 'Không thể xóa backup trên Google Drive.');
        }
    }

    public function downloadRemoteBackup(
        string $reference,
        GoogleDriveBackupBrowserService $browser,
        DatabaseService $service,
    ): void {
        $this->authorizePermission('database.download');
        $temporaryPath = tempnam(storage_path('framework'), 'drive-download-');

        if ($temporaryPath === false) {
            $this->notify('error', 'Không thể tạo file tạm để tải backup.');

            return;
        }

        try {
            $remote = $browser->download($reference, $temporaryPath);
            $service->importBackupFile($temporaryPath, $remote['name']);
            $this->notify('success', 'Đã tải backup Google Drive về kho local. Hãy kiểm tra trước khi RESTORE.');
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive remote backup download failed.', $e);
            $this->notify('error', 'Không thể tải backup Google Drive về local.');
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function restoreBackup(string $backupReference, DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');

        try {
            if ($service->restoreFromFile($backupReference)) {
                $this->notify('success', 'Khôi phục dữ liệu thành công!');
            }
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup restore failed.', $e);
            $this->notify('error', 'Khôi phục dữ liệu thất bại. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function deleteBackup(string $backupReference, DatabaseService $service): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $service->deleteBackup($backupReference);
            $this->notify('success', 'Đã xóa backup local.');
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup delete failed.', $e);
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
            $service->importBackupFile(
                $validated['sqlFile']->getRealPath(),
                $validated['sqlFile']->getClientOriginalName(),
            );
            $this->reset('sqlFile');
            $this->notify('success', 'Đã tải file backup vào kho local. Hãy kiểm tra trước khi RESTORE.');
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup upload failed.', $e);
            $message = 'Không thể nhập file backup. Vui lòng kiểm tra file SQL và log hệ thống.';
            $this->addError('sqlFile', $message);
            $this->notify('error', $message);
        }
    }

    public function openEmailModal(string $backupReference, DatabaseService $service): void
    {
        $this->authorizePermission('database.download');
        $backup = $service->getBackupDescriptor($backupReference, ['sql']);
        $path = $service->getDownloadPath($backupReference);

        if ($backup === null || $path === null) {
            $this->notify('error', 'File backup không tồn tại.');

            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->notify('error', 'Chỉ gửi được file backup có dung lượng tối đa 10MB.');

            return;
        }

        $this->emailBackupReference = $backupReference;
        $this->emailBackupName = $backup['name'];
        $this->backupEmail = (string) (auth('admin')->user()?->email ?? '');
        $this->resetErrorBag('backupEmail');
        $this->showEmailModal = true;
    }

    public function sendBackupEmail(DatabaseService $service): void
    {
        $this->authorizePermission('database.download');
        $validated = $this->validate([
            'emailBackupReference' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/'],
            'backupEmail' => ['required', 'email:rfc', 'max:255'],
        ]);
        $backup = $service->getBackupDescriptor($validated['emailBackupReference'], ['sql']);
        $path = $service->getDownloadPath($validated['emailBackupReference']);

        if ($backup === null || $path === null) {
            $this->notify('error', 'File backup không còn tồn tại.');

            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->notify('error', 'File backup vượt quá giới hạn 10MB.');

            return;
        }

        SendDatabaseBackupEmail::dispatch($backup['name'], $validated['backupEmail']);
        Log::info('Database backup email delivery queued.', ['actor_id' => auth('admin')->id()]);
        $this->showEmailModal = false;
        $this->emailBackupReference = '';
        $this->emailBackupName = '';
        $this->notify('success', 'Đã đưa yêu cầu gửi backup vào hàng đợi email.');
    }

    private function queueDriveUpload(string $fileName, GoogleDriveConnectionService $drive): void
    {
        $drive->markBackupQueued($fileName);
        UploadDatabaseBackupToGoogleDrive::dispatch($fileName, auth('admin')->id());
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, content: $message, message: $message);
        $this->dispatch('backup-operation-finished', type: $type, message: $message);
    }

    private function reportOperationError(string $message, Throwable $exception): void
    {
        Log::error($message, [
            'actor_id' => auth('admin')->id(),
            'exception' => $exception::class,
        ]);
    }
}
