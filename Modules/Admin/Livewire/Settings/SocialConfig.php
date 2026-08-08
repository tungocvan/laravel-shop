<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Modules\Admin\Services\Env\SocialConfigService;

class SocialConfig extends Component
{
    public array $form = [
        'GOOGLE_CLIENT_ID' => '',
        'GOOGLE_CLIENT_SECRET' => '',
        'FACEBOOK_CLIENT_ID' => '',
        'FACEBOOK_CLIENT_SECRET' => '',
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

    public function save(EnvManagerService $envManager, SocialConfigService $service)
    {
        $validation = $service->validateCredentials($this->form);
        
        if (!$validation['success']) {
            $this->dispatch('notify', type: 'error', message: $validation['message']);
            return;
        }

        $envManager->update($this->form);
        $this->dispatch('notify', type: 'success', message: 'Cấu hình SEO & Social đã được cập nhật thành công!');
    }

    public function render()
    {
        return view('Admin::livewire.settings.social-config');
    }
}