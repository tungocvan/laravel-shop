<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Modules\Admin\Services\Env\MailConfigService;

class MailConfig extends Component
{
    public array $form = [
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => '',
        'MAIL_PORT' => '587',
        'MAIL_USERNAME' => '',
        'MAIL_PASSWORD' => '',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => '',
        'MAIL_FROM_NAME' => '',
    ];

    public string $testEmail = ''; // Email nhận tin thử nghiệm

    public function mount(EnvManagerService $envManager)
    {
        $currentEnv = $envManager->getValues();
        foreach ($this->form as $key => $value) {
            $this->form[$key] = $currentEnv[$key] ?? $value;
        }
    }

    public function sendTest(MailConfigService $mailService)
    {
        $this->validate(['testEmail' => 'required|email']);

        $result = $mailService->testSendMail($this->form, $this->testEmail);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
    }

    public function save(EnvManagerService $envManager)
    {
        $envManager->update($this->form);
        $this->dispatch('notify', type: 'success', message: 'Cấu hình Email đã được lưu!');
    }

    public function render()
    {
        return view('Admin::livewire.settings.mail-config');
    }
}
