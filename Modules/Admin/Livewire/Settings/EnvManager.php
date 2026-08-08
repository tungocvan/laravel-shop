<?php
namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;

class EnvManager extends Component
{
    public string $activeTab = 'database';

    // Hàm này bây giờ sẽ được gọi bởi wire:click
    public function exportEnv(string $envType, EnvManagerService $service)
    {
        if ($service->exportToEnvironment($envType)) {
            $this->dispatch('notify', 
                type: 'success', 
                message: "Đã tạo thành công file .env.{$envType}!"
            );
        } else {
            $this->dispatch('notify', 
                type: 'error', 
                message: "Lỗi: Không thể tạo file. Kiểm tra quyền ghi (Permission)."
            );
        }
    }

    public function render()
    {
        // Lấy danh sách tabs từ logic bạn đã viết
        $tabs = $this->getTabsDefinition(); 
        return view('Admin::livewire.settings.env-manager', compact('tabs'));
    }

    private function getTabsDefinition() {
        // Trả về mảng tabs như bạn đã định nghĩa ở Controller trước đó
    }
}