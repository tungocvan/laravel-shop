<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Invoices\Services\GdtApiService;
use Throwable;

final class QuickGdtConnect extends Component
{
    protected GdtApiService $service;

    public bool $modalOpen = false;

    public bool $authenticated = false;

    public string $captchaSvg = '';

    public ?string $ckey = null;

    public ?string $cvalue = null;

    public ?string $error = null;

    public ?string $message = null;

    public function boot(GdtApiService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->authorizeConfigure();
        $this->authenticated = $this->service->hasToken();
    }

    public function openModal(): void
    {
        $this->authorizeConfigure();
        $this->resetValidation();
        $this->error = null;
        $this->message = null;
        $this->modalOpen = true;
        $this->refreshCaptcha();
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->cvalue = null;
        $this->error = null;
        $this->resetValidation();
    }

    public function refreshCaptcha(): void
    {
        $this->authorizeConfigure();
        $this->captchaSvg = '';
        $this->ckey = null;
        $this->cvalue = null;
        $this->error = null;
        $this->resetValidation('cvalue');

        try {
            $captcha = $this->service->loadCaptcha();
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Không thể tải captcha GDT: '.$exception->getMessage();

            return;
        }

        if (! isset($captcha['key'], $captcha['content'])) {
            $this->error = 'Không thể tải captcha từ hệ thống GDT. Vui lòng thử lại.';

            return;
        }

        $this->ckey = (string) $captcha['key'];
        $this->captchaSvg = (string) $captcha['content'];
    }

    public function connect(): void
    {
        $this->authorizeConfigure();
        $this->error = null;
        $this->message = null;

        $this->validate([
            'cvalue' => ['required', 'string', 'max:20'],
        ], [
            'cvalue.required' => 'Vui lòng nhập mã captcha.',
        ]);

        if (! $this->ckey || ! $this->captchaSvg) {
            $this->error = 'Captcha chưa sẵn sàng. Hãy tải captcha mới.';

            return;
        }

        try {
            $response = $this->service->login(
                (string) $this->cvalue,
                $this->ckey,
                (int) config('invoices.gdt.token_ttl', 36000),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Không thể kết nối GDT: '.$exception->getMessage();
            $this->refreshCaptcha();

            return;
        }

        if (($response['status'] ?? 'error') !== 'success') {
            $this->error = (string) ($response['message'] ?? 'Đăng nhập GDT không thành công.');
            $this->refreshCaptchaKeepingError();

            return;
        }

        $this->authenticated = true;
        $this->message = 'Đã kết nối GDT thành công. Bạn có thể chạy đồng bộ ngay.';
        $this->modalOpen = false;
        $this->captchaSvg = '';
        $this->ckey = null;
        $this->cvalue = null;
        $this->dispatch('gdt-connected');
    }

    public function disconnect(): void
    {
        $this->authorizeConfigure();
        $this->service->forgetToken();
        $this->authenticated = false;
        $this->message = 'Đã xóa phiên GDT trên server.';
        $this->error = null;
    }

    private function refreshCaptchaKeepingError(): void
    {
        $error = $this->error;
        $this->refreshCaptcha();
        $this->error = $error;
    }

    private function authorizeConfigure(): void
    {
        abort_unless(
            auth('admin')->check() && auth('admin')->user()->can('invoices-configure'),
            403,
        );
    }

    public function render(): View
    {
        return view('Invoices::livewire.quick-gdt-connect');
    }
}
