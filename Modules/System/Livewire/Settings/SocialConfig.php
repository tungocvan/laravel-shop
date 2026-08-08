<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Services\Env\EnvManagerService;

class SocialConfig extends Component
{
    public array $form = [
        'GOOGLE_CLIENT_ID' => '',
        'GOOGLE_CLIENT_SECRET' => '',
        'GOOGLE_REDIRECT' => '',
        'FACEBOOK_CLIENT_ID' => '',
        'FACEBOOK_CLIENT_SECRET' => '',
        'FACEBOOK_REDIRECT_URI' => '',
        'TINYMCE_API_KEY' => '',
        'GOOGLE_ANALYTICS_ID' => '',
    ];

    public function mount(EnvManagerService $envManager)
    {
        $currentEnv = $envManager->getValues();
        foreach ($this->form as $key => $value) {
            $this->form[$key] = $currentEnv[$key] ?? $value;
        }
    }

    public function save(EnvManagerService $envManager): void
    {
        if (! $envManager->update($this->form)) {
            $this->dispatch('notify', type: 'error', message: 'Không thể ghi file .env. Vui lòng kiểm tra quyền ghi.');
            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Cấu hình SEO & Social đã được cập nhật thành công!');
    }

    public function render()
    {
        return view('System::livewire.settings.social-config');
    }
}
