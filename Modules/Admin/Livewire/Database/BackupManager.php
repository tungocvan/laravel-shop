<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Database;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\Admin\Services\DatabaseService;
use Exception;

class BackupManager extends Component
{
    /**
     * Lắng nghe sự kiện để cập nhật danh sách khi có file mới được tạo
     */
    #[On('backup-updated')]
    public function refresh(): void
    {
        // Livewire tự động re-render khi state thay đổi hoặc có event
    }

    /**
     * Render danh sách file từ Service
     */
    public function render(DatabaseService $service)
    {
        return view('Admin::livewire.database.backup-manager', [
            'backups' => $service->getBackupFiles() // Gọi qua Service, không query trực tiếp
        ]);
    }

    /**
     * Xử lý khôi phục dữ liệu
     */
    public function restoreBackup(string $fileName, DatabaseService $service): void
    {
        try {
            // Logic restore nằm trọn trong Service
            $success = $service->restore($fileName);

            if ($success) {
                $this->dispatch('notify', ['type' => 'success', 'message' => 'Khôi phục dữ liệu thành công!']);
            }
        } catch (Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }
    public function restore(string $fileName, DatabaseService $service): void
    {
        try {
            $service->restore($fileName);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Hệ thống đã được khôi phục thành công!']);
            // Tùy chọn: Redirect hoặc Refresh trang để cập nhật cấu trúc mới
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
