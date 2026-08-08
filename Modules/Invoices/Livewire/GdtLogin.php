<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtConfigService;
use Throwable;

class GdtLogin extends Component
{
    protected GdtApiService $service;

    public $captchaSvg = ''; // gán mặc định rỗng

    public $ckey;

    public $cvalue;

    public bool $authenticated = false;

    public array $gdtConfig = [
        'base_url' => '',
        'username' => '',
        'password' => '',
        'verify_ssl' => true,
        'timeout' => 15,
        'token_ttl' => 36000,
        'cache_key' => 'gdt_token',
    ];

    public function boot(GdtApiService $service): void
    {
        $this->service = $service;
    }

    public function mount(GdtConfigService $configService): void
    {
        try {
            if ($configService->ensureDefaults()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable) {
            session()->flash('error', 'Không thể tự động bổ sung các biến GDT_* vào file .env.');
        }

        $this->gdtConfig = [
            'base_url' => (string) config('invoices.gdt.base_url'),
            'username' => (string) config('invoices.gdt.username'),
            // Không hydrate password vào public state của Livewire.
            'password' => '',
            'verify_ssl' => (bool) config('invoices.gdt.verify_ssl', true),
            'timeout' => (int) config('invoices.gdt.timeout', 15),
            'token_ttl' => (int) config('invoices.gdt.token_ttl', 36000),
            'cache_key' => (string) config('invoices.gdt.cache_key', 'gdt_token'),
        ];

        if ($this->service->hasToken()) {
            $this->authenticated = true;

            return;
        }

        $this->refreshCaptcha();
    }

    public function refreshCaptcha(): void
    {
        $this->captchaSvg = '';
        $this->ckey = null;
        $this->cvalue = null;
        $captcha = $this->service->loadCaptcha();

        if (isset($captcha['key'], $captcha['content'])) {
            $this->ckey = $captcha['key'];
            $this->captchaSvg = $captcha['content'];
        } else {
            session()->flash('error', 'Không thể tải captcha từ hệ thống GDT. Vui lòng thử lại sau.');
        }
    }

    public function saveGdtConfig(GdtConfigService $configService): void
    {
        $validated = $this->validate([
            'gdtConfig.base_url' => ['required', 'url:http,https', 'max:500'],
            'gdtConfig.username' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'gdtConfig.password' => ['nullable', 'string', 'max:500', 'not_regex:/[\r\n]/'],
            'gdtConfig.verify_ssl' => ['required', 'boolean'],
            'gdtConfig.timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'gdtConfig.token_ttl' => ['required', 'integer', 'min:60', 'max:604800'],
            'gdtConfig.cache_key' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.:-]+$/'],
        ], [
            'gdtConfig.base_url.url' => 'GDT_API_BASE_URL phải là URL HTTP/HTTPS hợp lệ.',
            'gdtConfig.cache_key.regex' => 'Cache key chỉ được chứa chữ, số và các ký tự _ . : -.',
        ]);

        $values = [
            'GDT_API_BASE_URL' => rtrim($validated['gdtConfig']['base_url'], '/'),
            'GDT_API_USERNAME' => $validated['gdtConfig']['username'],
            'GDT_API_VERIFY_SSL' => $validated['gdtConfig']['verify_ssl'] ? 'true' : 'false',
            'GDT_API_TIMEOUT' => (string) $validated['gdtConfig']['timeout'],
            'GDT_TOKEN_TTL' => (string) $validated['gdtConfig']['token_ttl'],
            'GDT_TOKEN_CACHE_KEY' => $validated['gdtConfig']['cache_key'],
        ];

        if ($validated['gdtConfig']['password'] !== '') {
            $values['GDT_API_PASSWORD'] = $validated['gdtConfig']['password'];
        }

        try {
            $configService->update($values);
            $this->service->forgetToken();
            Artisan::call('config:clear');
        } catch (Throwable) {
            session()->flash('error', 'Không thể lưu cấu hình GDT. Vui lòng kiểm tra quyền ghi file .env.');

            return;
        }

        $this->gdtConfig['password'] = '';
        $this->authenticated = false;
        session()->flash('success', 'Đã cập nhật cấu hình GDT. Phiên cũ đã được xóa.');
        $this->redirectRoute('admin.invoices.create-token');
    }

    public function login(): void
    {
        $this->validate([
            'cvalue' => ['required', 'string', 'max:20'],
        ], [
            'cvalue.required' => 'Vui lòng nhập captcha.',
        ]);

        // Kiểm tra token trong cache trước
        if (! $this->authenticated) {
            if (! $this->cvalue || ! $this->ckey) {
                session()->flash('error', 'Captcha chưa sẵn sàng hoặc chưa được nhập.');

                return;
            }

            $response = $this->service->login(
                $this->cvalue,
                $this->ckey,
                (int) config('invoices.gdt.token_ttl', 36000)
            );
            if (($response['status'] ?? 'error') !== 'success') {
                session()->flash('error', $response['message'] ?? 'Đăng nhập GDT không thành công.');

                return;
            }

            $this->authenticated = true;
        }

        $this->redirectRoute('admin.invoices.create-token');
    }

    public function deleteToken(): void
    {
        $this->service->forgetToken();
        $this->authenticated = false;
        $this->redirectRoute('admin.invoices.create-token');
    }

    public function render(): View
    {
        return view('Invoices::livewire.gdt-login');
    }
}
