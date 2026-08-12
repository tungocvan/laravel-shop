<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Env\MomoConfigService;
use Throwable;

class MomoConfig extends Component
{
    use AuthorizesSystemActions;

    public array $form = [
        'MOMO_ENDPOINT' => 'https://test-payment.momo.vn',
        'MOMO_PARTNER_CODE' => '',
        'MOMO_ACCESS_KEY' => '',
        'MOMO_SECRET_KEY' => '',
    ];

    public string $statusMessage = '';
    public bool $canUpdate = false;

    public function mount(MomoConfigService $service): void
    {
        $this->form = $service->publicValues();
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.env.update');
    }

    protected function rules(): array
    {
        return [
            'form.MOMO_ENDPOINT' => ['required', 'url:https', 'max:2048'],
            'form.MOMO_PARTNER_CODE' => ['required', 'string', 'max:128'],
            'form.MOMO_ACCESS_KEY' => ['nullable', 'string', 'max:512'],
            'form.MOMO_SECRET_KEY' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function testEndpoint(MomoConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validateOnly('form.MOMO_ENDPOINT');

        try {
            $result = $service->testEndpoint($this->form['MOMO_ENDPOINT']);
            $this->statusMessage = $result['message'];
            $this->dispatch('notify', type: $result['success'] ? 'success' : 'error', message: $result['message']);
        } catch (Throwable $e) {
            report($e);
            $this->statusMessage = 'Không thể kiểm tra endpoint MoMo.';
            $this->dispatch('notify', type: 'error', message: 'Endpoint MoMo không hợp lệ hoặc không thể kết nối.');
        }
    }

    public function save(MomoConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            if (!$service->save($this->form)) {
                throw new \RuntimeException('Environment update failed.');
            }
            $this->form['MOMO_ACCESS_KEY'] = '';
            $this->form['MOMO_SECRET_KEY'] = '';
            $this->dispatch('notify', type: 'success', message: 'Cấu hình MoMo đã được cập nhật.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật cấu hình MoMo. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.momo-config');
    }
}
