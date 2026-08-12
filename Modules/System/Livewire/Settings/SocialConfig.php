<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Env\SocialConfigService;
use Throwable;

class SocialConfig extends Component
{
    use AuthorizesSystemActions;

    public array $form = [];
    public array $configuredSecrets = [];
    public bool $canUpdate = false;

    public function mount(SocialConfigService $service): void
    {
        $this->form = $service->publicValues();
        $this->configuredSecrets = $service->configuredSecrets();
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.env.update');
    }

    protected function rules(): array
    {
        return [
            'form.GOOGLE_CLIENT_ID' => ['nullable', 'string', 'max:255'],
            'form.GOOGLE_CLIENT_SECRET' => ['nullable', 'string', 'max:1024'],
            'form.GOOGLE_REDIRECT' => ['nullable', 'url:http,https', 'max:2048'],
            'form.FACEBOOK_CLIENT_ID' => ['nullable', 'string', 'max:64'],
            'form.FACEBOOK_CLIENT_SECRET' => ['nullable', 'string', 'max:1024'],
            'form.FACEBOOK_REDIRECT_URI' => ['nullable', 'url:http,https', 'max:2048'],
            'form.TINYMCE_API_KEY' => ['nullable', 'string', 'max:1024'],
            'form.GOOGLE_ANALYTICS_ID' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i', 'max:32'],
        ];
    }

    public function save(SocialConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $this->validate();

        try {
            if (!$service->save($this->form)) {
                throw new \RuntimeException('Environment update failed.');
            }

            foreach (['GOOGLE_CLIENT_SECRET', 'FACEBOOK_CLIENT_SECRET', 'TINYMCE_API_KEY'] as $key) {
                $this->form[$key] = '';
            }
            $this->configuredSecrets = $service->configuredSecrets();
            $this->dispatch('notify', type: 'success', message: 'Cấu hình SEO & Social đã được cập nhật thành công!');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật cấu hình Social. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.social-config');
    }
}
