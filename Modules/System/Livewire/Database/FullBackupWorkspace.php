<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Jobs\UploadDatabaseBackupToGoogleDrive;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Cloud\GoogleDriveBackupBrowserService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\DatabaseService;
use Throwable;

class FullBackupWorkspace extends Component
{
    use AuthorizesSystemActions;

    public array $selectedNames = [];

    public bool $showRestoreModal = false;
    public string $restoreReference = '';
    public string $restoreName = '';

    public bool $showDeleteModal = false;
    public bool $deleteLocal = true;
    public bool $deleteDrive = false;

    public function render(DatabaseService $service, GoogleDriveConnectionService $drive, GoogleDriveBackupBrowserService $browser)
    {
        $user = auth('admin')->user() ?: auth()->user();
        $capabilities = [
            'backup' => (bool) $user?->can('database.backup'),
            'download' => (bool) $user?->can('database.download'),
            'restore' => (bool) $user?->can('database.restore'),
            'destroy' => (bool) $user?->can('database.destroy'),
        ];
        $status = $drive->status();
        $driveConnected = (bool) ($status['connected'] ?? false);
        $local = array_values(array_filter($service->getAllBackupFiles(), static fn (array $file): bool => (bool) ($file['is_full'] ?? false)));
        $remote = [];
        $remoteUnavailable = false;

        if ($driveConnected) {
            try {
                $remote = $browser->listBackups(100);
            } catch (Throwable $e) {
                $remoteUnavailable = true;
                Log::warning('Full backup workspace Drive listing failed.', ['exception' => $e::class]);
            }
        }

        $catalog = [];
        foreach ($local as $file) {
            $catalog[$file['name']] = ['name' => $file['name'], 'size' => $file['size'], 'time' => $file['time'], 'local' => $file, 'remote' => null];
        }
        foreach ($remote as $file) {
            $catalog[$file['name']] ??= ['name' => $file['name'], 'size' => $file['size'], 'time' => strtotime((string) ($file['modified_at'] ?? '')) ?: 0, 'local' => null, 'remote' => null];
            $catalog[$file['name']]['remote'] = $file;
        }
        uasort($catalog, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);
        $catalog = array_values($catalog);
        $visibleNames = array_column($catalog, 'name');
        $this->selectedNames = array_values(array_intersect($this->selectedNames, $visibleNames));

        return view('System::livewire.database.full-backup-workspace', compact('catalog', 'driveConnected', 'remoteUnavailable', 'capabilities'));
    }

    public function createBackup(DatabaseService $service): void
    {
        $this->authorizePermission('database.backup');
        try {
            $created = $service->createFullDatabaseBackup();
            $this->notify('success', 'Đã tạo Full Backup '.$created['name'].'. File mới đã xuất hiện trong Backup Catalog.');
        } catch (Throwable $e) {
            $this->fail('Full database backup failed.', $e, 'Không thể tạo Full Backup.');
        }
    }

    public function createBackupAndUpload(DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('database.backup');
        if (! ($drive->status()['connected'] ?? false)) {
            $this->notify('error', 'Google Drive chưa được kết nối.');
            return;
        }
        try {
            $created = $service->createFullDatabaseBackup();
            $drive->markBackupQueued($created['name']);
            UploadDatabaseBackupToGoogleDrive::dispatch($created['name'], auth('admin')->id());
            $this->notify('success', 'Đã tạo Full Backup và đưa upload Google Drive vào hàng đợi.');
        } catch (Throwable $e) {
            $this->fail('Full backup and Drive upload failed.', $e, 'Không thể hoàn tất Backup & Upload.');
        }
    }

    public function uploadLocal(string $reference, DatabaseService $service, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('database.backup');
        $backup = $service->getBackupDescriptor($reference, ['sql']);
        if ($backup === null || ! ($drive->status()['connected'] ?? false)) {
            $this->notify('error', 'Backup local không tồn tại hoặc Google Drive chưa kết nối.');
            return;
        }
        $drive->markBackupQueued($backup['name']);
        UploadDatabaseBackupToGoogleDrive::dispatch($backup['name'], auth('admin')->id());
        $this->notify('success', 'Đã đưa backup vào hàng đợi upload Google Drive.');
    }

    public function downloadRemote(string $reference, GoogleDriveBackupBrowserService $browser, DatabaseService $service): void
    {
        $this->authorizePermission('database.download');
        $temporary = tempnam(storage_path('framework'), 'full-backup-drive-');
        if ($temporary === false) {
            $this->notify('error', 'Không thể tạo file tạm.');
            return;
        }
        try {
            $remote = $browser->download($reference, $temporary);
            $service->importBackupFile($temporary, $remote['name']);
            $this->notify('success', 'Đã tải backup từ Google Drive về Local và xác minh file.');
        } catch (Throwable $e) {
            $this->fail('Full backup Drive download failed.', $e, 'Không thể tải backup về Local.');
        } finally {
            @unlink($temporary);
        }
    }

    public function openRestore(string $reference, DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        $backup = $service->getBackupDescriptor($reference, ['sql']);
        if ($backup === null || ! ($backup['is_full'] ?? false)) {
            $this->notify('error', 'Full Backup local không còn tồn tại hoặc không hợp lệ.');
            return;
        }
        $this->restoreReference = $reference;
        $this->restoreName = $backup['name'];
        $this->showRestoreModal = true;
    }

    public function confirmRestore(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        if ($this->restoreReference === '') {
            return;
        }
        try {
            $service->restoreFromFile($this->restoreReference);
            $this->showRestoreModal = false;
            $this->restoreReference = '';
            $this->restoreName = '';
            $this->notify('success', 'Restore database thành công. Safety Backup đã được tạo trước khi restore.');
        } catch (Throwable $e) {
            $this->fail('Full database restore failed.', $e, 'Restore database thất bại. Hãy kiểm tra log và Safety Backup.');
        }
    }

    public function openDelete(): void
    {
        $this->authorizePermission('database.destroy');
        if ($this->selectedNames === []) {
            $this->notify('error', 'Vui lòng chọn ít nhất một backup.');
            return;
        }
        $this->deleteLocal = true;
        $this->deleteDrive = false;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(DatabaseService $service, GoogleDriveBackupBrowserService $browser): void
    {
        $this->authorizePermission('database.destroy');
        if (! $this->deleteLocal && ! $this->deleteDrive) {
            $this->notify('error', 'Hãy chọn ít nhất một vị trí cần xóa.');
            return;
        }
        $localByName = [];
        foreach ($service->getAllBackupFiles() as $file) {
            $localByName[$file['name']] = $file['id'];
        }
        $remoteByName = [];
        if ($this->deleteDrive) {
            try {
                foreach ($browser->listBackups(100) as $file) {
                    $remoteByName[$file['name']] = $file['reference'];
                }
            } catch (Throwable $e) {
                $this->fail('Drive catalog unavailable during delete.', $e, 'Không thể đọc Google Drive để xóa. Local chưa bị xóa.');
                return;
            }
        }
        $deleted = 0;
        foreach ($this->selectedNames as $name) {
            try {
                if ($this->deleteLocal && isset($localByName[$name])) {
                    $service->deleteBackup($localByName[$name]);
                    $deleted++;
                }
                if ($this->deleteDrive && isset($remoteByName[$name])) {
                    $browser->delete($remoteByName[$name]);
                    $deleted++;
                }
            } catch (Throwable $e) {
                Log::error('Full backup catalog delete item failed.', ['name' => $name, 'exception' => $e::class]);
            }
        }
        $this->selectedNames = [];
        $this->showDeleteModal = false;
        $this->notify('success', "Đã hoàn tất xóa {$deleted} bản sao tại các vị trí được chọn.");
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, content: $message, message: $message);
    }

    private function fail(string $log, Throwable $e, string $message): void
    {
        Log::error($log, ['actor_id' => auth('admin')->id(), 'exception' => $e::class]);
        $this->notify('error', $message);
    }
}
