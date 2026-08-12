<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Env\SystemMailConfigService;
use Throwable;

class MailConfig extends Component
{
    use AuthorizesSystemActions;

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

    public string $testEmail = '';
    public bool $canUpdate = false;

    public function mount(SystemMailConfigService $service): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.env.update');
        $this->form = $service->publicConfig() + ['MAIL_PASSWORD' => ''];
        $this->form['MAIL_PASSWORD'] = '';
    }

    protected function rules(): array
    {
        return [
            'form.MAIL_MAILER' => ['required', 'in:smtp'],
            'form.MAIL_HOST' => ['required', 'string', 'max:255'],
            'form.MAIL_PORT' => ['required', 'integer', 'min:1', 'max:65535'],
            'form.MAIL_USERNAME' => ['nullable', 'string', 'max:255'],
            'form.MAIL_PASSWORD' => ['nullable', 'string', 'max:4096'],
            'form.MAIL_ENCRYPTION' => ['nullable', 'in:tls,ssl,none'],
            'form.MAIL_FROM_ADDRESS' => ['required', 'email', 'max:255'],
            'form.MAIL_FROM_NAME' => ['required', 'string', 'max:255'],
            'testEmail' => ['required', 'email', 'max:255'],
        ];
    }

    public function sendTest(SystemMailConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            $result = $service->test($this->form, $this->testEmail, auth('admin')->id());
            $this->dispatch(
                'notify',
                type: $result['success'] ? 'success' : 'error',
                message: $result['message'],
            );
        } catch (Throwable $e) {
            Log::warning('MailConfig test send failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể gửi email kiểm tra. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function save(SystemMailConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validateOnly('form.MAIL_MAILER');
        $this->validate([
            'form.MAIL_MAILER' => ['required', 'in:smtp'],
            'form.MAIL_HOST' => ['required', 'string', 'max:255'],
            'form.MAIL_PORT' => ['required', 'integer', 'min:1', 'max:65535'],
            'form.MAIL_USERNAME' => ['nullable', 'string', 'max:255'],
            'form.MAIL_PASSWORD' => ['nullable', 'string', 'max:4096'],
            'form.MAIL_ENCRYPTION' => ['nullable', 'in:tls,ssl,none'],
            'form.MAIL_FROM_ADDRESS' => ['required', 'email', 'max:255'],
            'form.MAIL_FROM_NAME' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $service->save($this->form, auth('admin')->id());

            if (! $result['success']) {
                $this->dispatch('notify', type: 'error', message: $result['message']);
                return;
            }

            $this->form['MAIL_PASSWORD'] = '';
            $this->dispatch('notify', type: 'success', message: $result['message']);
        } catch (Throwable $e) {
            Log::error('MailConfig save failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu cấu hình Email. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.mail-config');
    }
}
