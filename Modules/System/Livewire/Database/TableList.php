<?php

namespace Modules\System\Livewire\Database;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
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

    public function boot(DatabaseService $service): void
    {
        $this->service = $service;
    }

    public function updatedSearch(): void
    {
        $this->resetVisibleSelectionState();
    }

    public function updatedModuleFilter(): void
    {
        $this->resetVisibleSelectionState();
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

    public function backupFull(): void
    {
        $this->authorizePermission('database.backup');

        try {
            $this->service->backupFullDatabase();
            $this->notify('success', 'Backup toàn bộ dữ liệu thành công!');
        } catch (\Throwable $e) {
            $this->reportOperationError('Full database backup failed.', $e);
            $this->notify('error', 'Backup database thất bại. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function exportTable(string $tableName): void
    {
        $this->authorizePermission('database.backup');

        try {
            $this->service->backupTable($tableName);
            $this->notify('success', "Export bảng {$tableName} thành công!");
        } catch (\Throwable $e) {
            $this->reportOperationError('Table export failed.', $e, ['table' => $tableName]);
            $this->notify('error', 'Export bảng thất bại. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function exportSelected(): void
    {
        $this->authorizePermission('database.backup');

        if ($this->selectedTables === []) {
            $this->notify('error', 'Vui lòng chọn ít nhất một bảng để export.');

            return;
        }

        try {
            $this->selectedExportFile = $this->service->backupTablesAsZip($this->selectedTables);
            $this->notify('success', 'Đã export các bảng đã chọn thành file ZIP!');
        } catch (\Throwable $e) {
            $this->reportOperationError('Bulk table export failed.', $e, ['tables' => $this->selectedTables]);
            $this->notify('error', 'Export các bảng đã chọn thất bại.');
        }
    }

    public function restoreTable(string $tableName): void
    {
        $this->authorizePermission('database.restore');

        try {
            if (! $this->service->restoreTable($tableName)) {
                $this->notify('error', 'Không tìm thấy file backup của bảng đã chọn.');

                return;
            }

            $this->notify('success', "Restore bảng {$tableName} thành công!");
        } catch (\Throwable $e) {
            $this->reportOperationError('Table restore failed.', $e, ['table' => $tableName]);
            $this->notify('error', 'Restore bảng thất bại. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function openImportModal(string $tableName): void
    {
        $this->authorizePermission('database.restore');

        try {
            $this->service->assertAllowedTable($tableName);
            $this->resetValidation('importFile');
            $this->importFile = null;
            $this->importTargetTable = $tableName;
            $this->showImportModal = true;
        } catch (\Throwable $e) {
            $this->reportOperationError('Open table import rejected.', $e, ['table' => $tableName]);
            $this->notify('error', 'Không thể import vào bảng đã chọn.');
        }
    }

    public function closeImportModal(): void
    {
        if ($this->isImporting) {
            return;
        }

        $this->showImportModal = false;
        $this->importTargetTable = null;
        $this->importFile = null;
        $this->resetValidation('importFile');
    }

    public function importTable(): void
    {
        $this->authorizePermission('database.restore');

        if ($this->isImporting || ! $this->importTargetTable) {
            return;
        }

        $this->validate([
            'importFile' => ['required', 'file', 'max:102400'],
        ], [
            'importFile.required' => 'Vui lòng chọn file SQL.',
            'importFile.file' => 'File upload không hợp lệ.',
            'importFile.max' => 'File SQL không được vượt quá 100 MB.',
        ]);

        if (strtolower($this->importFile->getClientOriginalExtension()) !== 'sql') {
            $this->addError('importFile', 'Chỉ chấp nhận file .sql.');

            return;
        }

        $this->isImporting = true;
        $tableName = $this->importTargetTable;

        try {
            $path = $this->importFile->getRealPath();
            $this->service->importTableFromFile($tableName, $path);
            $this->notify('success', "Import bảng {$tableName} thành công!");
            $this->closeImportStateAfterSuccess();
        } catch (\Throwable $e) {
            $this->reportOperationError('Table import failed.', $e, ['table' => $tableName]);
            $message = str_contains($e->getMessage(), 'Dữ liệu cũ đã được phục hồi')
                ? 'Import bảng thất bại. Dữ liệu cũ đã được phục hồi.'
                : 'Import bảng thất bại. Vui lòng kiểm tra log hệ thống.';
            $this->notify('error', $message);
        } finally {
            $this->isImporting = false;
        }
    }

    public function truncateTable(string $tableName): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $this->service->truncateTable($tableName);
            $this->notify('success', "Đã làm sạch dữ liệu bảng {$tableName}");
        } catch (\Throwable $e) {
            $this->reportOperationError('Table truncate failed.', $e, ['table' => $tableName]);
            $this->notify('error', 'Không thể làm sạch bảng đã chọn.');
        }
    }

    public function dropTable(string $tableName): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $this->service->dropTable($tableName);
            $this->notify('success', "Đã xóa bảng {$tableName}");
            $this->selectedTables = array_values(array_filter(
                $this->selectedTables,
                static fn (string $selected): bool => $selected !== $tableName,
            ));
        } catch (\Throwable $e) {
            $this->reportOperationError('Table drop failed.', $e, ['table' => $tableName]);
            $this->notify('error', 'Không thể xóa bảng đã chọn.');
        }
    }

    public function openRestoreModal(): void
    {
        $this->authorizePermission('database.restore');

        $this->backupFiles = array_values(array_filter(
            $this->service->getAllBackupFiles(),
            static fn (array $file): bool => $file['is_full'],
        ));
        $this->showRestoreModal = true;
    }

    public function closeRestoreModal(): void
    {
        if ($this->isRestoring) {
            return;
        }

        $this->showRestoreModal = false;
        $this->selectedBackupFile = null;
    }

    public function restoreDatabase(): void
    {
        $this->authorizePermission('database.restore');

        if ($this->isRestoring) {
            return;
        }

        if (! $this->selectedBackupFile) {
            $this->notify('error', 'Vui lòng chọn file backup.');

            return;
        }

        $this->isRestoring = true;

        try {
            $this->service->restoreFromFile($this->selectedBackupFile);
            $this->notify('success', 'Restore database thành công.');
            $this->showRestoreModal = false;
            $this->selectedBackupFile = null;
        } catch (\Throwable $e) {
            $this->reportOperationError('Full database restore failed.', $e, ['backup' => $this->selectedBackupFile]);
            $this->notify('error', 'Restore database thất bại. Vui lòng kiểm tra log hệ thống.');
        } finally {
            $this->isRestoring = false;
        }
    }

    public function render()
    {
        return view('System::livewire.database.table-list', [
            'tables' => $this->service->getAllTables($this->search, $this->moduleFilter),
            'modules' => $this->service->getModuleOptions(),
        ]);
    }

    private function resetVisibleSelectionState(): void
    {
        $this->selectAll = false;
        $this->selectedTables = [];
        $this->selectedExportFile = null;
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, content: $message, message: $message);
    }

    private function reportOperationError(string $message, \Throwable $exception, array $context = []): void
    {
        Log::error($message, $context + [
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);
    }

    private function closeImportStateAfterSuccess(): void
    {
        $this->showImportModal = false;
        $this->importTargetTable = null;
        $this->importFile = null;
        $this->resetValidation('importFile');
    }
}
