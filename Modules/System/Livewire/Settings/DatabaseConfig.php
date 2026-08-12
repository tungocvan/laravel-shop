<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Database\DatabaseConfigService;
use Throwable;

class DatabaseConfig extends Component
{
    use AuthorizesSystemActions;

    public array $form = [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
    ];

    public string $connectionStatus = '';
    public bool $canUpdate = false;

    public function mount(DatabaseConfigService $service): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.env.update');
        $this->form = $service->publicConfig() + ['DB_PASSWORD' => ''];
    }

    protected function rules(): array
    {
        return [
            'form.DB_CONNECTION' => ['required', 'in:mysql,pgsql'],
            'form.DB_HOST' => ['required', 'string', 'max:255'],
            'form.DB_PORT' => ['required', 'integer', 'min:1', 'max:65535'],
            'form.DB_DATABASE' => ['required', 'string', 'max:255'],
            'form.DB_USERNAME' => ['required', 'string', 'max:255'],
            'form.DB_PASSWORD' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function updatedForm(): void
    {
        $this->connectionStatus = '';
    }

    public function testConnection(DatabaseConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            $result = $service->test($this->form, auth('admin')->id());
            $this->connectionStatus = $result['success'] ? 'connected' : 'failed';
            $this->dispatch(
                'notify',
                type: $result['success'] ? 'success' : 'error',
                message: $result['message'],
            );
        } catch (Throwable $e) {
            Log::warning('DatabaseConfig connection test failed.', ['exception' => $e::class]);
            $this->connectionStatus = 'failed';
            $this->dispatch('notify', type: 'error', message: 'Không thể kiểm tra kết nối Database. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function save(DatabaseConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            $result = $service->save($this->form, auth('admin')->id());

            if (! $result['success']) {
                $this->connectionStatus = 'failed';
                $this->dispatch('notify', type: 'error', message: $result['message']);
                return;
            }

            $this->form['DB_PASSWORD'] = '';
            $this->connectionStatus = 'connected';
            $this->dispatch('notify', type: 'success', message: $result['message']);
        } catch (Throwable $e) {
            Log::error('DatabaseConfig save failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật cấu hình Database. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.database-config');
    }
}
