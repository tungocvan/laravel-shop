<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Invoices\Services\GdtExcelSandboxService;
use Throwable;

final class GdtExcelSandbox extends Component
{
    protected GdtExcelSandboxService $service;

    public string $taxCode = '';
    public string $password = '';
    public string $captchaSvg = '';
    public ?string $captchaKey = null;
    public string $captchaValue = '';
    public ?string $connectedTaxCode = null;
    public string $fromDate = '';
    public string $toDate = '';
    public string $invoiceType = 'sold';
    public array $files = [];
    public ?string $message = null;
    public ?string $error = null;

    public function boot(GdtExcelSandboxService $service): void { $this->service = $service; }

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->connectedTaxCode = $this->service->connectedTaxCode($this->userId());
        $this->refreshFiles();
        if (! $this->connectedTaxCode) $this->refreshCaptcha();
    }

    public function refreshCaptcha(): void
    {
        $this->authorizeAccess();
        $this->error = null;
        $this->captchaSvg = '';
        $this->captchaKey = null;
        $this->captchaValue = '';
        try {
            $captcha = $this->service->loadCaptcha($this->userId());
            if (! isset($captcha['key'], $captcha['content'])) throw new \RuntimeException('GDT không trả captcha hợp lệ.');
            $this->captchaKey = (string) $captcha['key'];
            $this->captchaSvg = (string) $captcha['content'];
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function connect(): void
    {
        $this->authorizeAccess();
        $validated = $this->validate([
            'taxCode' => ['required','string','max:30','regex:/^[0-9A-Za-z-]+$/'],
            'password' => ['required','string','max:255'],
            'captchaValue' => ['required','string','max:20'],
        ]);
        if (! $this->captchaKey) { $this->error = 'Captcha chưa sẵn sàng.'; return; }
        try {
            $this->service->login($this->userId(), $validated['taxCode'], $validated['password'], $this->captchaKey, $validated['captchaValue']);
            $this->connectedTaxCode = $validated['taxCode'];
            $this->password = '';
            $this->captchaValue = '';
            $this->captchaSvg = '';
            $this->captchaKey = null;
            $this->message = 'Kết nối GDT thành công.';
            $this->error = null;
        } catch (Throwable $e) {
            $this->password = '';
            $this->error = $e->getMessage();
            $this->refreshCaptchaKeepingError();
        }
    }

    public function disconnect(): void
    {
        $this->authorizeAccess();
        $this->service->disconnect($this->userId());
        $this->connectedTaxCode = null;
        $this->password = '';
        $this->message = 'Đã đóng phiên test GDT.';
        $this->refreshCaptcha();
    }

    public function sync(): void
    {
        $this->authorizeAccess();
        $validated = $this->validate([
            'fromDate' => ['required','date'],
            'toDate' => ['required','date','after_or_equal:fromDate'],
            'invoiceType' => ['required','in:sold,purchase'],
        ]);
        try {
            $result = $this->service->export($this->userId(), $validated['fromDate'], $validated['toDate'], $validated['invoiceType']);
            $this->message = $result['count'] > 0 ? "Đã đồng bộ {$result['count']} hóa đơn và tạo file Excel." : 'Không có hóa đơn trong khoảng ngày đã chọn.';
            $this->error = null;
            $this->refreshFiles();
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function download(string $taxCode, string $filename)
    {
        $this->authorizeAccess();

        return response()->download($this->service->filePath($this->userId(), $taxCode, $filename), $filename);
    }

    public function refreshFiles(): void { $this->files = $this->service->files($this->userId()); }

    private function refreshCaptchaKeepingError(): void
    {
        $error = $this->error;
        $this->refreshCaptcha();
        $this->error = $error;
    }

    private function userId(): int { return (int) auth('admin')->id(); }

    private function authorizeAccess(): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-configure'), 403);
    }

    public function render(): View { return view('Invoices::livewire.gdt-excel-sandbox'); }
}
