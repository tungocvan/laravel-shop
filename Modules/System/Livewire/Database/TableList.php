<?php

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\Cloud\GoogleDriveModuleSnapshotService;
use Modules\System\Services\Database\ModuleDependencyService;
use Modules\System\Services\Database\ModuleSnapshotDeletionService;
use Modules\System\Services\Database\ModuleSnapshotService;
use Modules\System\Services\DatabaseService;

#[Title('Quản lý Cơ sở dữ liệu')]
class TableList extends Component
{
    use AuthorizesSystemActions;
    use WithFileUploads;

    protected DatabaseService $service;

    public string $search = '';
    public string $moduleFilter = '';
    public array $selectedTables = [];
    public bool $selectAll = false;
    public array $backupFiles = [];
    public ?string $selectedBackupFile = null;
    public bool $showRestoreModal = false;
    public bool $isRestoring = false;
    public bool $showImportModal = false;
    public ?string $importTargetTable = null;
    public $importFile = null;
    public bool $isImporting = false;
    public ?string $selectedExportFile = null;
    public array $moduleLocalSnapshots = [];
    public array $moduleRemoteSnapshots = [];
    public array $moduleDependencies = [];
    public bool $moduleDriveConnected = false;
    public bool $moduleCloudUnavailable = false;
    public bool $showModuleRestoreModal = false;
    public ?string $selectedModuleSnapshotReference = null;
    public bool $isModuleRestoring = false;

    public function boot(DatabaseService $service): void { $this->service = $service; }
    public function updatedSearch(): void { $this->resetVisibleSelectionState(); }

    public function updatedModuleFilter(): void
    {
        $this->resetVisibleSelectionState();
        $this->resetModuleSnapshotState();
        $this->refreshModuleSnapshots();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $tables = $this->service->getAllTables($this->search, $this->moduleFilter);
            $this->selectedTables = array_column($tables, 'name');
            return;
        }
        $this->selectedTables = [];
    }

    public function updatedSelectedTables(): void
    {
        $visible = array_column($this->service->getAllTables($this->search, $this->moduleFilter), 'name');
        $this->selectAll = $visible !== [] && count(array_intersect($visible, $this->selectedTables)) === count($visible);
        $this->selectedExportFile = null;
    }

    public function refreshModuleSnapshots(): void
    {
        if ($this->moduleFilter === '' || $this->moduleFilter === 'Unknown') {
            $this->moduleLocalSnapshots = [];
            $this->moduleRemoteSnapshots = [];
            $this->moduleDependencies = [];
            $this->moduleDriveConnected = false;
            $this->moduleCloudUnavailable = false;
            return;
        }

        try {
            $snapshots = app(ModuleSnapshotService::class);
            $drive = app(GoogleDriveConnectionService::class);
            $this->moduleDependencies = app(ModuleDependencyService::class)->dependenciesFor($this->moduleFilter);
            $this->moduleLocalSnapshots = $snapshots->listLocal($this->moduleFilter, 30);
            $this->moduleDriveConnected = (bool) ($drive->status()['connected'] ?? false);
            $this->moduleRemoteSnapshots = [];
            $this->moduleCloudUnavailable = false;

            if ($this->moduleDriveConnected) {
                try {
                    $this->moduleRemoteSnapshots = app(GoogleDriveModuleSnapshotService::class)->list($this->moduleFilter, 30);
                } catch (\Throwable $e) {
                    $this->moduleCloudUnavailable = true;
                    $this->reportOperationError('Module snapshot Google Drive listing failed.', $e, ['module' => $this->moduleFilter]);
                }
            }
        } catch (\Throwable $e) {
            $this->moduleLocalSnapshots = [];
            $this->moduleRemoteSnapshots = [];
            $this->moduleDependencies = [];
            $this->reportOperationError('Module snapshot catalog refresh failed.', $e, ['module' => $this->moduleFilter]);
        }
    }

    public function backupModule(ModuleSnapshotService $snapshots): void
    {
        $this->authorizePermission('database.backup');
        $module = $this->moduleFilter;
        if ($module === '' || $module === 'Unknown') { $this->notify('error', 'Vui lòng chọn một Module có ownership rõ ràng.'); return; }
        try { $created = $snapshots->create($module); $this->refreshModuleSnapshots(); $this->notify('success', "Đã tạo Module Snapshot {$created['name']} cho {$module}."); }
        catch (\Throwable $e) { $this->reportOperationError('Module snapshot creation failed.', $e, ['module' => $module]); $this->notify('error', 'Không thể tạo Module Snapshot. Vui lòng kiểm tra log hệ thống.'); }
    }

    public function backupModuleAndUpload(ModuleSnapshotService $snapshots, GoogleDriveModuleSnapshotService $cloud): void
    {
        $this->authorizePermission('database.backup'); $module = $this->moduleFilter;
        if ($module === '' || $module === 'Unknown') { $this->notify('error', 'Vui lòng chọn một Module có ownership rõ ràng.'); return; }
        try { $created = $snapshots->create($module); $cloud->uploadLocal($module, $created['reference']); $this->refreshModuleSnapshots(); $this->notify('success', "Đã backup {$module} và đồng bộ Module Snapshot lên Google Drive."); }
        catch (\Throwable $e) { $this->reportOperationError('Module snapshot backup and Drive upload failed.', $e, ['module' => $module]); $this->refreshModuleSnapshots(); $this->notify('error', 'Không thể hoàn tất Backup & Upload Module Snapshot. Bản local nếu đã tạo vẫn được giữ lại.'); }
    }

    public function uploadModuleSnapshot(string $reference, GoogleDriveModuleSnapshotService $cloud): void
    {
        $this->authorizePermission('database.backup'); $module = $this->moduleFilter;
        try { $cloud->uploadLocal($module, $reference); $this->refreshModuleSnapshots(); $this->notify('success', 'Đã upload Module Snapshot lên Google Drive.'); }
        catch (\Throwable $e) { $this->reportOperationError('Module snapshot Drive upload failed.', $e, ['module' => $module]); $this->notify('error', 'Không thể upload Module Snapshot lên Google Drive.'); }
    }

    public function downloadModuleSnapshot(string $reference, GoogleDriveModuleSnapshotService $cloud): void
    {
        $this->authorizePermission('database.download'); $module = $this->moduleFilter;
        try { $cloud->downloadToLocal($module, $reference); $this->refreshModuleSnapshots(); $this->notify('success', 'Đã tải Module Snapshot từ Google Drive về local và kiểm tra package.'); }
        catch (\Throwable $e) { $this->reportOperationError('Module snapshot Drive download failed.', $e, ['module' => $module]); $this->notify('error', 'Không thể tải Module Snapshot từ Google Drive về local.'); }
    }

    public function deleteLocalModuleSnapshot(string $reference, ModuleSnapshotDeletionService $deletion): void
    {
        $this->authorizePermission('database.destroy'); $module = $this->moduleFilter;
        try { $deleted = $deletion->deleteLocal($module, $reference); $this->refreshModuleSnapshots(); $this->notify('success', "Đã xóa Local Snapshot {$deleted['name']}. Bản Google Drive nếu có vẫn được giữ nguyên."); }
        catch (\Throwable $e) { $this->reportOperationError('Local module snapshot delete failed.', $e, ['module' => $module]); $this->notify('error', 'Không thể xóa Local Snapshot. Vui lòng kiểm tra log hệ thống.'); }
    }

    public function deleteRemoteModuleSnapshot(string $reference, GoogleDriveModuleSnapshotService $cloud): void
    {
        $this->authorizePermission('database.destroy'); $module = $this->moduleFilter;
        try { $cloud->delete($module, $reference); $this->refreshModuleSnapshots(); $this->notify('success', 'Đã xóa Module Snapshot trên Google Drive. Bản local nếu có vẫn được giữ nguyên.'); }
        catch (\Throwable $e) { $this->reportOperationError('Google Drive module snapshot delete failed.', $e, ['module' => $module]); $this->notify('error', 'Không thể xóa Module Snapshot trên Google Drive.'); }
    }

    public function openModuleRestoreModal(string $reference, ModuleSnapshotService $snapshots): void
    {
        $this->authorizePermission('database.restore'); $module = $this->moduleFilter;
        try {
            $snapshot = $snapshots->resolveLocalReference($reference, $module);
            if ($snapshot === null) { throw new \RuntimeException('Module snapshot local không tồn tại.'); }
            $snapshots->validatePackage($snapshot['absolute_path'], $module, enforceSchema: true);
            $this->selectedModuleSnapshotReference = $reference; $this->showModuleRestoreModal = true;
        } catch (\Throwable $e) { $this->reportOperationError('Open module snapshot restore rejected.', $e, ['module' => $module]); $this->notify('error', 'Module Snapshot không tương thích hoặc không còn tồn tại.'); }
    }

    public function closeModuleRestoreModal(): void
    {
        if ($this->isModuleRestoring) { return; }
        $this->showModuleRestoreModal = false; $this->selectedModuleSnapshotReference = null;
    }

    public function restoreModuleSnapshot(ModuleSnapshotService $snapshots): void
    {
        $this->authorizePermission('database.restore');
        if ($this->isModuleRestoring || $this->selectedModuleSnapshotReference === null) { return; }
        $this->isModuleRestoring = true; $module = $this->moduleFilter;
        try {
            $result = $snapshots->restore($this->selectedModuleSnapshotReference, $module);
            $this->showModuleRestoreModal = false; $this->selectedModuleSnapshotReference = null; $this->refreshModuleSnapshots();
            $this->notify('success', "Restore Module {$module} thành công. Safety Snapshot: {$result['safety_snapshot']['name']}.");
        } catch (\Throwable $e) {
            $this->reportOperationError('Module snapshot restore failed.', $e, ['module' => $module, 'snapshot' => $this->selectedModuleSnapshotReference]);
            $this->notify('error', 'Restore Module thất bại. Hệ thống đã cố gắng rollback an toàn; vui lòng kiểm tra log.');
        } finally { $this->isModuleRestoring = false; }
    }

    public function backupFull(): void
    {
        $this->authorizePermission('database.backup');
        try { $this->service->backupFullDatabase(); $this->notify('success', 'Backup toàn bộ dữ liệu thành công!'); }
        catch (\Throwable $e) { $this->reportOperationError('Full database backup failed.', $e); $this->notify('error', 'Backup database thất bại. Vui lòng kiểm tra log hệ thống.'); }
    }

    public function exportTable(string $tableName): void
    {
        $this->authorizePermission('database.backup');
        try { $this->service->backupTable($tableName); $this->notify('success', "Export bảng {$tableName} thành công!"); }
        catch (\Throwable $e) { $this->reportOperationError('Table export failed.', $e, ['table' => $tableName]); $this->notify('error', 'Export bảng thất bại. Vui lòng kiểm tra log hệ thống.'); }
    }

    public function exportSelected(): void
    {
        $this->authorizePermission('database.backup');
        if ($this->selectedTables === []) { $this->notify('error', 'Vui lòng chọn ít nhất một bảng để export.'); return; }
        try {
            $fileName = $this->service->backupTablesAsZip($this->selectedTables);
            $this->selectedExportFile = $this->service->getBackupReference($fileName, ['zip']);
            if ($this->selectedExportFile === null) { throw new \RuntimeException('Không thể tạo download reference cho file ZIP.'); }
            $this->notify('success', 'Đã export các bảng đã chọn thành file ZIP!');
        } catch (\Throwable $e) { $this->reportOperationError('Bulk table export failed.', $e, ['tables' => $this->selectedTables]); $this->notify('error', 'Export các bảng đã chọn thất bại.'); }
    }

    public function restoreTable(string $tableName): void
    {
        $this->authorizePermission('database.restore');
        try { if (! $this->service->restoreTable($tableName)) { $this->notify('error', 'Không tìm thấy file backup của bảng đã chọn.'); return; } $this->notify('success', "Restore bảng {$tableName} thành công!"); }
        catch (\Throwable $e) { $this->reportOperationError('Table restore failed.', $e, ['table' => $tableName]); $this->notify('error', 'Restore bảng thất bại. Vui lòng kiểm tra log hệ thống.'); }
    }

    public function openImportModal(string $tableName): void
    {
        $this->authorizePermission('database.restore');
        try { $this->service->assertAllowedTable($tableName); $this->resetValidation('importFile'); $this->importFile = null; $this->importTargetTable = $tableName; $this->showImportModal = true; }
        catch (\Throwable $e) { $this->reportOperationError('Open table import rejected.', $e, ['table' => $tableName]); $this->notify('error', 'Không thể import vào bảng đã chọn.'); }
    }

    public function closeImportModal(): void
    {
        if ($this->isImporting) { return; }
        $this->showImportModal = false; $this->importTargetTable = null; $this->importFile = null; $this->resetValidation('importFile');
    }

    public function importTable(): void
    {
        $this->authorizePermission('database.restore');
        if ($this->isImporting || ! $this->importTargetTable) { return; }
        $this->validate(['importFile' => ['required', 'file', 'max:102400']], ['importFile.required' => 'Vui lòng chọn file SQL.', 'importFile.file' => 'File upload không hợp lệ.', 'importFile.max' => 'File SQL không được vượt quá 100 MB.']);
        if (strtolower($this->importFile->getClientOriginalExtension()) !== 'sql') { $this->addError('importFile', 'Chỉ chấp nhận file .sql.'); return; }
        $this->isImporting = true; $tableName = $this->importTargetTable;
        try { $path = $this->importFile->getRealPath(); $this->service->importTableFromFile($tableName, $path); $this->notify('success', "Import bảng {$tableName} thành công!"); $this->closeImportStateAfterSuccess(); }
        catch (\Throwable $e) { $this->reportOperationError('Table import failed.', $e, ['table' => $tableName]); $this->notify('error', 'Import bảng thất bại. Vui lòng tải lại dữ liệu và kiểm tra log hệ thống.'); }
        finally { $this->isImporting = false; }
    }

    public function truncateTable(string $tableName): void
    {
        $this->authorizePermission('database.destroy');
        try { $this->service->truncateTable($tableName); $this->notify('success', "Đã làm sạch dữ liệu bảng {$tableName}"); }
        catch (\Throwable $e) { $this->reportOperationError('Table truncate failed.', $e, ['table' => $tableName]); $this->notify('error', 'Không thể làm sạch bảng đã chọn.'); }
    }

    public function dropTable(string $tableName): void
    {
        $this->authorizePermission('database.destroy');
        try { $this->service->dropTable($tableName); $this->notify('success', "Đã xóa bảng {$tableName}"); $this->selectedTables = array_values(array_filter($this->selectedTables, static fn (string $selected): bool => $selected !== $tableName)); }
        catch (\Throwable $e) { $this->reportOperationError('Table drop failed.', $e, ['table' => $tableName]); $this->notify('error', 'Không thể xóa bảng đã chọn.'); }
    }

    public function openRestoreModal(): void
    {
        $this->authorizePermission('database.restore');
        $this->backupFiles = array_values(array_filter($this->service->getAllBackupFiles(), static fn (array $file): bool => $file['is_full']));
        $this->showRestoreModal = true;
    }

    public function closeRestoreModal(): void
    {
        if ($this->isRestoring) { return; }
        $this->showRestoreModal = false; $this->selectedBackupFile = null;
    }

    public function restoreDatabase(): void
    {
        $this->authorizePermission('database.restore');
        if ($this->isRestoring) { return; }
        if (! $this->selectedBackupFile) { $this->notify('error', 'Vui lòng chọn file backup.'); return; }
        $this->isRestoring = true;
        try { $this->service->restoreFromFile($this->selectedBackupFile); $this->notify('success', 'Restore database thành công.'); $this->showRestoreModal = false; $this->selectedBackupFile = null; }
        catch (\Throwable $e) { $this->reportOperationError('Full database restore failed.', $e, ['backup' => $this->selectedBackupFile]); $this->notify('error', 'Restore database thất bại. Vui lòng kiểm tra log hệ thống.'); }
        finally { $this->isRestoring = false; }
    }

    public function render()
    {
        return view('System::livewire.database.table-list', [
            'tables' => $this->service->getAllTables($this->search, $this->moduleFilter),
            'modules' => $this->service->getModuleOptions(),
            'canBackup' => (bool) auth('admin')->user()?->can('database.backup'),
            'canDownload' => (bool) auth('admin')->user()?->can('database.download'),
            'canRestore' => (bool) auth('admin')->user()?->can('database.restore'),
            'canDestroy' => (bool) auth('admin')->user()?->can('database.destroy'),
        ]);
    }

    private function resetVisibleSelectionState(): void { $this->selectAll = false; $this->selectedTables = []; $this->selectedExportFile = null; }

    private function resetModuleSnapshotState(): void
    {
        $this->moduleLocalSnapshots = []; $this->moduleRemoteSnapshots = []; $this->moduleDependencies = []; $this->moduleCloudUnavailable = false; $this->showModuleRestoreModal = false; $this->selectedModuleSnapshotReference = null;
    }

    private function notify(string $type, string $message): void { $this->dispatch('notify', type: $type, content: $message, message: $message); }

    private function reportOperationError(string $message, \Throwable $exception, array $context = []): void
    {
        Log::error($message, $context + ['exception' => $exception::class]);
    }

    private function closeImportStateAfterSuccess(): void
    {
        $this->showImportModal = false; $this->importTargetTable = null; $this->importFile = null; $this->resetValidation('importFile');
    }
}
