<?php

namespace Modules\System\Livewire\Database;

use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\DatabaseService;

#[Title('Quản lý Cơ sở dữ liệu')]
class TableList extends Component
{
    use AuthorizesSystemActions;

    // State
    public $search = '';

    public $selectedTables = [];

    public $selectAll = false;

    // UI Feedback
    public $loadingAction = null; // Lưu tên bảng đang được xử lý

    public $backupFiles = [];

    public $selectedBackupFile = null;

    public $showRestoreModal = false;

    public $isRestoring = false;

    public function boot(DatabaseService $service)
    {
        // Inject Service
        $this->service = $service;
    }

    // --- Actions ---

    public function updatedSearch()
    {
        // $this->resetPage(); // Nếu có phân trang
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $tables = $this->service->getAllTables($this->search);
            $this->selectedTables = array_column($tables, 'name');
        } else {
            $this->selectedTables = [];
        }
    }

    public function backupFull()
    {
        $this->authorizePermission('database.backup');

        try {
            // Gọi service đã fix ở trên
            $this->service->backupFullDatabase();

            // Thành công -> Thông báo xanh
            $this->dispatch('notify', type: 'success', content: 'Backup toàn bộ dữ liệu thành công!');

        } catch (\Exception $e) {
            // Thất bại -> Thông báo đỏ & Hiện lỗi chi tiết
            $this->dispatch('notify', type: 'error', content: 'Thất bại: '.$e->getMessage());
        }
    }

    public function exportTable($tableName)
    {
        $this->authorizePermission('database.backup');

        try {
            $this->service->backupTable($tableName);
            $this->dispatch('notify', type: 'success', content: "Export bảng {$tableName} thành công!");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', content: 'Lỗi export: '.$e->getMessage());
        }
    }

    public function restoreTable($tableName)
    {
        $this->authorizePermission('database.restore');

        try {
            $this->service->restoreTable($tableName);
            $this->dispatch('notify', type: 'success', content: "Restore bảng {$tableName} thành công!");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', content: 'Lỗi restore: '.$e->getMessage());
        }
    }

    public function truncateTable($tableName)
    {
        $this->authorizePermission('database.destroy');

        try {
            $this->service->truncateTable($tableName);
            $this->dispatch('notify', type: 'success', content: "Đã làm sạch dữ liệu bảng {$tableName}");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', content: $e->getMessage());
        }
    }

    public function dropTable($tableName)
    {
        $this->authorizePermission('database.destroy');

        try {
            $this->service->dropTable($tableName);
            $this->dispatch('notify', type: 'success', content: "Đã xóa bảng {$tableName}");
            // Reset selection nếu bảng bị xóa đang được chọn
            if (($key = array_search($tableName, $this->selectedTables)) !== false) {
                unset($this->selectedTables[$key]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', content: $e->getMessage());
        }
    }

    public function render(DatabaseService $service)
    {
        // Lấy dữ liệu fresh mỗi lần render để update trạng thái file/rows
        $tables = $service->getAllTables($this->search);

        return view('System::livewire.database.table-list', [
            'tables' => $tables,
        ]);
    }

    public function openRestoreModal()
    {
        $this->authorizePermission('database.restore');

        $this->backupFiles = array_values(array_filter(
            app(DatabaseService::class)->getAllBackupFiles(),
            static fn (array $file): bool => $file['is_full'],
        ));
        $this->showRestoreModal = true;
    }

    public function restoreDatabase()
    {
        $this->authorizePermission('database.restore');

        if ($this->isRestoring) {
            return;
        }

        if (! $this->selectedBackupFile) {
            $this->dispatch('notify', type: 'error', message: 'Vui lòng chọn file backup');

            return;
        }

        $this->isRestoring = true;

        try {
            app(DatabaseService::class)
                ->restoreFromFile($this->selectedBackupFile);

            $this->dispatch('notify', type: 'success', message: 'Restore database thành công');

            $this->showRestoreModal = false;

        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Restore thất bại: '.$e->getMessage());
        } finally {
            $this->isRestoring = false;
        }
    }
}
