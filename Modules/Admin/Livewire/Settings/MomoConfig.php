<?php
namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Illuminate\Support\Facades\Http;

class MomoConfig extends Component
{
    public array $form = [
        'MOMO_ENDPOINT' => '',
        'MOMO_PARTNER_CODE' => '',
        'MOMO_ACCESS_KEY' => '',
        'MOMO_SECRET_KEY' => '',
    ];

    public string $statusMessage = '';

    public function mount(EnvManagerService $envManager)
    {
        $currentEnv = $envManager->getValues();
        foreach ($this->form as $key => $value) {
            $this->form[$key] = $currentEnv[$key] ?? $value;
        }
    }

    public function testEndpoint()
    {
        try {
            $response = Http::timeout(5)->get($this->form['MOMO_ENDPOINT'] ?: 'https://test-payment.momo.vn');
            $this->statusMessage = $response->successful() ? '✅ Endpoint hoạt động' : '❌ Endpoint lỗi: ' . $response->status();
        } catch (\Exception $e) {
            $this->statusMessage = '❌ Không thể kết nối';
        }
    }

    public function save(EnvManagerService $envManager)
    {
        $envManager->update($this->form);
        $this->dispatch('notify', type: 'success', message: 'Cấu hình Momo đã được lưu và sao lưu!');
    }

    public function render()
    {
        return view('Admin::livewire.settings.momo-config');
    }
}