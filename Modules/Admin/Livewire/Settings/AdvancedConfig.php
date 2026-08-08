<?php
namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Modules\Admin\Services\Env\SystemConfigService;

class AdvancedConfig extends Component
{
    public array $form = [
        'QUEUE_CONNECTION' => 'database',
        'NODEJS_SERVER_URL' => 'http://localhost:3000',
        'BRIDGE_SECRET_KEY' => '',
    ];

    public string $nodeStatus = '';

    public string $queueStatus = '';


    public function testQueue(SystemConfigService $service)
    {
        $this->queueStatus = 'Pending...';

        // Debug: Kiểm tra driver đang dùng thực tế
        //dd(config('queue.default'));

        $service->dispatchTestJob();

        $this->dispatch('notify', type: 'info', message: 'Lệnh dispatch đã chạy!');
    }

    public function refreshQueueStatus(SystemConfigService $service)
    {
        // Lấy giá trị mới từ Service
        $newStatus = $service->checkQueueStatus();

        if ($this->queueStatus !== $newStatus) {
            $this->queueStatus = $newStatus;

            // Nếu đã thành công, bắn thông báo cho Admin
            if (str_contains($newStatus, 'Success')) {
                $this->dispatch('notify', type: 'success', message: 'Hàng đợi thực thi hoàn tất!');
            }
        }
    }

    public function mount(EnvManagerService $envManager)
    {
        $currentEnv = $envManager->getValues();
        foreach ($this->form as $key => $value) {
            $this->form[$key] = $currentEnv[$key] ?? $value;
        }
    }

    public function checkNode(SystemConfigService $service)
    {
        $result = $service->pingNodeJS($this->form['NODEJS_SERVER_URL'], $this->form['BRIDGE_SECRET_KEY']);
        $this->nodeStatus = $result['success'] ? 'online' : 'offline';
        $this->dispatch('notify', type: $result['success'] ? 'success' : 'error', message: $result['message']);
    }

    public function save(EnvManagerService $envManager)
    {
        $envManager->update($this->form);
        $this->dispatch('notify', type: 'success', message: 'Cấu hình Hệ thống đã được cập nhật!');
    }

    public function render()
    {
        return view('Admin::livewire.settings.advanced-config');
    }
}
