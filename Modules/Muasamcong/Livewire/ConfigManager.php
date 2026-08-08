<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Modules\Muasamcong\Services\MuasamcongConfigService;
use Modules\Muasamcong\Services\MuaSamCongService;
use Throwable;

class ConfigManager extends Component
{
    public array $form = [
        'origin' => '',
        'verify_ssl' => true,
        'timeout' => 20,
        'user_agent' => '',
        'smart_token' => '',
        'session_cookie' => '',
        'pricing_endpoint' => '',
        'contractor_endpoint' => '',
        'portal_referer' => '',
        'pricing_referer' => '',
        'page_size' => 20,
    ];

    public bool $hasSmartToken = false;

    public bool $hasSessionCookie = false;

    public string $tokenTestStatus = '';

    public string $tokenTestMessage = '';

    public function mount(MuasamcongConfigService $configService): void
    {
        try {
            if ($configService->ensureDefaults()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable) {
            session()->flash('error', 'Không thể tự động bổ sung các biến MUASAMCONG_* vào file .env.');
        }

        $this->form = [
            'origin' => (string) config('muasamcong.origin'),
            'verify_ssl' => (bool) config('muasamcong.verify_ssl', true),
            'timeout' => (int) config('muasamcong.timeout', 20),
            'user_agent' => (string) config('muasamcong.user_agent'),
            // Secret không được hydrate vào public state của Livewire.
            'smart_token' => '',
            'session_cookie' => '',
            'pricing_endpoint' => (string) config('muasamcong.endpoints.pricing'),
            'contractor_endpoint' => (string) config('muasamcong.endpoints.contractor_search'),
            'portal_referer' => (string) config('muasamcong.referers.portal'),
            'pricing_referer' => (string) config('muasamcong.referers.pricing'),
            'page_size' => (int) config('muasamcong.page_size', 20),
        ];
        $this->hasSmartToken = trim((string) config('muasamcong.smart_token')) !== '';
        $this->hasSessionCookie = trim((string) config('muasamcong.session_cookie')) !== '';
    }

    public function save(MuasamcongConfigService $configService): void
    {
        $validated = $this->validate([
            'form.origin' => ['required', 'url:http,https', 'max:500'],
            'form.verify_ssl' => ['required', 'boolean'],
            'form.timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'form.user_agent' => ['required', 'string', 'max:500', 'not_regex:/[\r\n]/'],
            'form.smart_token' => ['nullable', 'string', 'max:4000', 'not_regex:/[\r\n]/'],
            'form.session_cookie' => ['nullable', 'string', 'max:16000', 'not_regex:/[\r\n]/'],
            'form.pricing_endpoint' => ['required', 'url:http,https', 'max:1000'],
            'form.contractor_endpoint' => ['required', 'url:http,https', 'max:1000'],
            'form.portal_referer' => ['required', 'url:http,https', 'max:1000'],
            'form.pricing_referer' => ['required', 'url:http,https', 'max:1000'],
            'form.page_size' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $values = [
            'MUASAMCONG_ORIGIN' => rtrim($validated['form']['origin'], '/'),
            'MUASAMCONG_VERIFY_SSL' => $validated['form']['verify_ssl'] ? 'true' : 'false',
            'MUASAMCONG_TIMEOUT' => (string) $validated['form']['timeout'],
            'MUASAMCONG_USER_AGENT' => $validated['form']['user_agent'],
            'MUASAMCONG_PRICING_ENDPOINT' => $validated['form']['pricing_endpoint'],
            'MUASAMCONG_CONTRACTOR_ENDPOINT' => $validated['form']['contractor_endpoint'],
            'MUASAMCONG_PORTAL_REFERER' => $validated['form']['portal_referer'],
            'MUASAMCONG_PRICING_REFERER' => $validated['form']['pricing_referer'],
            'MUASAMCONG_PAGE_SIZE' => (string) $validated['form']['page_size'],
        ];

        if ($validated['form']['smart_token'] !== '') {
            $values['MUASAMCONG_SMART_TOKEN'] = $validated['form']['smart_token'];
        }

        if ($validated['form']['session_cookie'] !== '') {
            $values['MUASAMCONG_SESSION_COOKIE'] = $validated['form']['session_cookie'];
        }

        try {
            $configService->update($values);
            Artisan::call('config:clear');
        } catch (Throwable) {
            session()->flash('error', 'Không thể lưu cấu hình Mua sắm công. Vui lòng kiểm tra quyền ghi file .env.');

            return;
        }

        $this->form['smart_token'] = '';
        $this->form['session_cookie'] = '';
        session()->flash('success', 'Đã cập nhật cấu hình Mua sắm công.');
        $this->redirectRoute('muasamcong.config');
    }

    public function testToken(MuaSamCongService $service): void
    {
        $validated = $this->validate([
            'form.smart_token' => ['nullable', 'string', 'max:4000', 'not_regex:/[\r\n]/'],
            'form.session_cookie' => ['nullable', 'string', 'max:16000', 'not_regex:/[\r\n]/'],
        ]);

        $this->tokenTestStatus = '';
        $this->tokenTestMessage = '';

        $result = $service->testSmartToken(
            $validated['form']['smart_token'] !== ''
                ? $validated['form']['smart_token']
                : null,
            $validated['form']['session_cookie'] !== ''
                ? $validated['form']['session_cookie']
                : null
        );

        if ($result['success'] ?? false) {
            $total = (int) ($result['data']['total'] ?? 0);
            $this->tokenTestStatus = 'success';
            $this->tokenTestMessage = "Token hợp lệ. Truy vấn thử trả về {$total} kết quả.";

            return;
        }

        $this->tokenTestStatus = 'error';
        $this->tokenTestMessage = $result['message'] ?? 'Token không hợp lệ hoặc đã hết hạn.';
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.config-manager');
    }
}
