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
use Modules\System\Services\Database\DatabaseBackupCatalogService;
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

    public array $selectedLocalBackups = [];

    public array $selectedRemoteBackups = [];

    public bool $showRenameModal = false;

    public string $renameScope = '';

    public string $renameReference = '';

    public string $renameName = '';

    public bool $showBulkDeleteModal = false;

    public string $bulkDeleteScope = '';

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

        $localIds = array_column($backups, 'id');
        $remoteIds = array_column($remoteBackups, 'reference');
        $this->selectedLocalBackups = array_values(array_intersect($this->selectedLocalBackups, $localIds));
        $this->selectedRemoteBackups = array_values(array_intersect($this->selectedRemoteBackups, $remoteIds));

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

    public function toggleSelectAllLocal(DatabaseService $service): void
    {
        $visible = array_slice($service->getAllBackupFiles(), 0, self::RECENT_BACKUP_LIMIT);
        $references = array_column($visible, 'id');
        $this->selectedLocalBackups = count($this->selectedLocalBackups) === count($references) ? [] : $references;
    }

    public function toggleSelectAllRemote(GoogleDriveBackupBrowserService $browser): void
    {
        try {
            $references = array_column($browser->listBackups(100), 'reference');
            $this->selectedRemoteBackups = count($this->selectedRemoteBackups) === count($references) ? [] : $references;
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive remote backup select-all failed.', $e);
            $this->notify('error', 'Không thể chọn danh sách backup Google Drive.');
        }
    }

    public function openLocalRenameModal(DatabaseBackupCatalogService $catalog): void
    {
        $this->authorizePermission('database.destroy');
        $reference = $this->singleSelectedReference($this->selectedLocalBackups);

        if ($reference === null) {
            $this->notify('error', 'Vui lòng chọn đúng 1 backup local để đổi tên.');

            return;
        }

        $backup = $catalog->resolveReference($reference, ['sql']);

        if ($backup === null) {
            $this->notify('error', 'Backup local không còn tồn tại.');

            return;
        }

        $this->openRename('local', $reference, $backup['name']);
    }

    public function openRemoteRenameModal(GoogleDriveBackupBrowserService $browser): void
    {
        $this->authorizePermission('database.destroy');
        $reference = $this->singleSelectedReference($this->selectedRemoteBackups);

        if ($reference === null) {
            $this->notify('error', 'Vui lòng chọn đúng 1 backup Google Drive để đổi tên.');

            return;
        }

        try {
            $backup = $browser->describe($reference);
            $this->openRename('remote', $reference, $backup['name']);
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive remote backup rename preparation failed.', $e);
            $this->notify('error', 'Không thể mở chức năng đổi tên Google Drive.');
        }
    }

    public function submitRename(
        DatabaseBackupCatalogService $catalog,
        GoogleDriveBackupBrowserService $browser,
    ): void {
        $this->authorizePermission('database.destroy');
        $validated = $this->validate([
            'renameScope' => ['required', 'in:local,remote'],
            'renameReference' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/'],
            'renameName' => ['required', 'string', 'max:124'],
        ], [
            'renameName.required' => 'Vui lòng nhập tên backup mới.',
            'renameName.max' => 'Tên backup quá dài.',
        ]);

        try {
            if ($validated['renameScope'] === 'local') {
                $renamed = $catalog->renameReference($validated['renameReference'], $validated['renameName']);
                $this->selectedLocalBackups = [];
                $message = 'Đã đổi tên backup local thành '.$renamed['name'].'.';
            } else {
                $renamed = $browser->rename($validated['renameReference'], $validated['renameName']);
                $this->selectedRemoteBackups = [];
                $message = 'Đã đổi tên backup Google Drive thành '.$renamed['name'].'.';
            }

            $this->closeRenameModal();
            $this->notify('success', $message);
        } catch (Throwable $e) {
            $this->reportOperationError('Database backup rename failed.', $e);
            $this->addError('renameName', $e->getMessage());
        }
    }

    public function closeRenameModal(): void
    {
        $this->showRenameModal = false;
        $this->renameScope = '';
        $this->renameReference = '';
        $this->renameName = '';
        $this->resetErrorBag('renameName');
    }

    public function openBulkDeleteModal(string $scope): void
    {
        $this->authorizePermission('database.destroy');

        if (! in_array($scope, ['local', 'remote'], true)) {
            return;
        }

        $selected = $scope === 'local' ? $this->selectedLocalBackups : $this->selectedRemoteBackups;

        if ($selected === []) {
            $this->notify('error', 'Vui lòng chọn ít nhất 1 backup để xóa.');

            return;
        }

        $this->bulkDeleteScope = $scope;
        $this->showBulkDeleteModal = true;
    }

    public function confirmBulkDelete(
        DatabaseService $service,
        GoogleDriveBackupBrowserService $browser,
    ): void {
        $this->authorizePermission('database.destroy');
        $scope = $this->bulkDeleteScope;
        $references = array_values(array_unique($scope === 'local' ? $this->selectedLocalBackups : $this->selectedRemoteBackups));

        if (! in_array($scope, ['local', 'remote'], true) || $references === []) {
            $this->showBulkDeleteModal = false;

            return;
        }

        $deleted = 0;
        $failed = 0;

        foreach ($references as $reference) {
            try {
                if ($scope === 'local') {
                    $service->deleteBackup($reference);
                } else {
                    $browser->delete($reference);
                }
                $deleted++;
            } catch (Throwable $e) {
                $failed++;
                $this->reportOperationError('Database backup bulk delete item failed.', $e);
            }
        }

        if ($scope === 'local') {
            $this->selectedLocalBackups = [];
        } else {
            $this->selectedRemoteBackups = [];
        }

        $this->showBulkDeleteModal = false;
        $this->bulkDeleteScope = '';

        if ($failed === 0) {
            $this->notify('success', "Đã xóa {$deleted} backup ".($scope === 'local' ? 'local.' : 'trên Google Drive.'));
        } else {
            $this->notify('error', "Đã xóa {$deleted} backup, {$failed} backup không thể xóa. Vui lòng kiểm tra log.");
        }
    }

    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
        $this->bulkDeleteScope = '';
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

    public function uploadToGoogleDrive(string $backupReference, DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
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

    public function retryGoogleDriveUpload(string $backupReference, DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
        $this->uploadToGoogleDrive($backupReference, $service, $drive);
    }

    public function deleteRemoteBackup(string $reference, GoogleDriveBackupBrowserService $browser): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $browser->delete($reference);
            $this->selectedRemoteBackups = array_values(array_diff($this->selectedRemoteBackups, [$reference]));
            $this->notify('success', 'Đã xóa backup khỏi Google Drive.');
        } catch (Throwable $e) {
            $this->reportOperationError('Google Drive remote backup delete failed.', $e);
            $this->notify('error', 'Không thể xóa backup trên Google Drive.');
        }
    }

    public function downloadRemoteBackup(string $reference, GoogleDriveBackupBrowserService $browser, DatabaseService $service): void
    {
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
            $this->selectedLocalBackups = array_values(array_diff($this->selectedLocalBackups, [$backupReference]));
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
            $service->importBackupFile($validated['sqlFile']->getRealPath(), $validated['sqlFile']->getClientOriginalName());
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

    private function singleSelectedReference(array $selected): ?string
    {
        $selected = array_values(array_unique(array_filter($selected, 'is_string')));

        return count($selected) === 1 ? $selected[0] : null;
    }

    private function openRename(string $scope, string $reference, string $name): void
    {
        $this->renameScope = $scope;
        $this->renameReference = $reference;
        $this->renameName = $name;
        $this->resetErrorBag('renameName');
        $this->showRenameModal = true;
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
