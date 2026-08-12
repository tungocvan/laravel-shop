<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Env\AdvancedConfigService;
use Modules\System\Services\Env\SystemConfigService;
use Throwable;

class AdvancedConfig extends Component
{
    use AuthorizesSystemActions;

    public array $form = [
        'QUEUE_CONNECTION' => 'database',
        'NODEJS_SERVER_URL' => 'http://127.0.0.1:3000',
        'BRIDGE_SECRET_KEY' => '',
    ];

    public string $nodeStatus = '';
    public string $queueStatus = '';
    public int $queuePollAttempts = 0;
    public bool $canUpdate = false;

    public function mount(AdvancedConfigService $config): void
    {
        $this->form = $config->publicValues();
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.env.update');
    }

    protected function rules(): array
    {
        return [
            'form.QUEUE_CONNECTION' => ['required', 'in:sync,database,redis'],
            'form.NODEJS_SERVER_URL' => ['required', 'url:http,https', 'max:2048'],
            'form.BRIDGE_SECRET_KEY' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function testQueue(SystemConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validateOnly('form.QUEUE_CONNECTION');
        $this->queueStatus = 'Pending...';
        $this->queuePollAttempts = 0;
        $service->dispatchTestJob();
        $this->dispatch('notify', type: 'info', message: 'Đã dispatch queue test job.');
    }

    public function refreshQueueStatus(SystemConfigService $service): void
    {
        $this->authorizePermission('system.env.update');

        if (++$this->queuePollAttempts > 15) {
            $this->queueStatus = 'Timeout';
            return;
        }

        $newStatus = $service->checkQueueStatus();
        if ($this->queueStatus !== $newStatus) {
            $this->queueStatus = $newStatus;
            if (str_contains($newStatus, 'Success')) {
                $this->dispatch('notify', type: 'success', message: 'Hàng đợi thực thi hoàn tất!');
            }
        }
    }

    public function checkNode(SystemConfigService $service, AdvancedConfigService $config): void
    {
        $this->authorizePermission('system.env.update');
        $this->validateOnly('form.NODEJS_SERVER_URL');
        $this->validateOnly('form.BRIDGE_SECRET_KEY');

        try {
            $candidate = $config->resolveForOperation($this->form);
            $result = $service->pingNodeJS($candidate['url'], $candidate['secret']);
            $this->nodeStatus = $result['success'] ? 'online' : 'offline';
            $this->dispatch('notify', type: $result['success'] ? 'success' : 'error', message: $result['message']);
        } catch (Throwable $e) {
            report($e);
            $this->nodeStatus = 'offline';
            $this->dispatch('notify', type: 'error', message: 'Không thể kiểm tra NodeJS. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function save(AdvancedConfigService $config): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            if (!$config->save($this->form)) {
                throw new \RuntimeException('Environment update failed.');
            }
            $this->form['BRIDGE_SECRET_KEY'] = '';
            $this->dispatch('notify', type: 'success', message: 'Cấu hình Hệ thống đã được cập nhật!');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật cấu hình. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.advanced-config');
    }
}
