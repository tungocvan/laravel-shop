<?php
namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Modules\Admin\Services\Env\EnvBackupService;
use Modules\Admin\Services\Database\DbConnectionService;
use Illuminate\Support\Facades\Artisan;

class DatabaseConfig extends Component
{
    // Typed properties cho Form
    public array $form = [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST'       => '127.0.0.1',
        'DB_PORT'       => '3306',
        'DB_DATABASE'   => '',
        'DB_USERNAME'   => '',
        'DB_PASSWORD'   => '',
    ];

    public string $connectionStatus = '';

    /**
     * Khởi tạo dữ liệu từ file .env hiện tại
     */
    public function mount(EnvManagerService $envManager)
    {
        $currentEnv = $envManager->getValues();
        foreach ($this->form as $key => $value) {
            $this->form[$key] = $currentEnv[$key] ?? $value;
        }
    }

    /**
     * Test kết nối trước khi lưu
     */
    public function testConnection(DbConnectionService $dbService)
    {
        $result = $dbService->testConnection($this->form);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->connectionStatus = 'connected';
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
            $this->connectionStatus = 'failed';
        }
    }

    /**
     * Lưu cấu hình vào .env sau khi đã test thành công
     */
    public function save(
        EnvManagerService $envManager,
        EnvBackupService $backupService,
        DbConnectionService $dbService
    ) {
        // 1. Kiểm tra kết nối lại một lần nữa cho an toàn
        $test = $dbService->testConnection($this->form);
        if (!$test['success']) {
            return $this->dispatch('notify', type: 'error', message: 'Không thể lưu vì kết nối DB thất bại!');
        }

        // 2. Backup file .env cũ
        $backupService->createBackup();

        // 3. Cập nhật file .env mới
        $envManager->update($this->form);

        // 4. Xóa cache cấu hình hệ thống
        Artisan::call('config:clear');

        $this->dispatch('notify', type: 'success', message: 'Cấu hình Database đã được cập nhật và sao lưu!');
    }

    public function render()
    {
        return view('Admin::livewire.settings.database-config'); //
    }
}
