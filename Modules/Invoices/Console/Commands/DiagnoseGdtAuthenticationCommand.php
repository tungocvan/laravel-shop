<?php

namespace Modules\Invoices\Console\Commands;

use Illuminate\Console\Command;
use Modules\Invoices\Services\GdtApiService;

class DiagnoseGdtAuthenticationCommand extends Command
{
    protected $signature = 'invoices:gdt-auth-diagnose';

    protected $description = 'Diagnose the GDT captcha/authentication flow without printing authentication secrets';

    public function handle(GdtApiService $gdt): int
    {
        if (! app()->environment('local')) {
            $this->error('Lệnh diagnostic này chỉ được phép chạy trong môi trường local.');

            return self::FAILURE;
        }

        $captcha = $gdt->loadCaptcha();
        $ckey = is_string($captcha['key'] ?? null) ? $captcha['key'] : null;

        if (! $ckey) {
            $this->error('Không tải được captcha GDT. Xem laravel.log để lấy diagnostic metadata.');

            return self::FAILURE;
        }

        $image = $captcha['content'] ?? $captcha['image'] ?? null;
        if (! is_string($image) || trim($image) === '') {
            $this->error('Response captcha không có dữ liệu SVG/image để hiển thị.');

            return self::FAILURE;
        }

        $captchaPath = storage_path('app/invoices/gdt-diagnostics/captcha.svg');
        if (! is_dir(dirname($captchaPath))) {
            mkdir(dirname($captchaPath), 0755, true);
        }

        if (str_starts_with($image, 'data:image/svg+xml;base64,')) {
            $image = base64_decode(substr($image, strlen('data:image/svg+xml;base64,')), true) ?: '';
        }

        if (! str_contains($image, '<svg')) {
            $this->error('Captcha nhận được không phải SVG hợp lệ để lưu diagnostic.');

            return self::FAILURE;
        }

        file_put_contents($captchaPath, $image);

        $this->info('Captcha GDT đã được tạo.');
        $this->line('Mở file này để đọc captcha: '.$captchaPath);
        $this->line('WSL/Windows có thể dùng: explorer.exe "'.str_replace('/', '\\\\', $captchaPath).'"');

        $cvalue = (string) $this->secret('Nhập mã captcha');
        if ($cvalue === '') {
            $this->error('Captcha không được để trống.');

            return self::FAILURE;
        }

        $result = $gdt->login($cvalue, $ckey);

        $this->newLine();
        $this->table(['Field', 'Result'], [
            ['status', (string) ($result['status'] ?? 'unknown')],
            ['http_status', (string) ($result['http_status'] ?? 'n/a')],
            ['code', (string) ($result['code'] ?? 'n/a')],
            ['message', (string) ($result['message'] ?? 'n/a')],
        ]);
        $this->line('Transport/cookie metadata: xem bản ghi GDT mới nhất trong storage/logs/laravel.log.');
        $this->warn('Command không in username, password, token, captcha value hoặc cookie values.');

        return ($result['status'] ?? null) === 'success' ? self::SUCCESS : self::FAILURE;
    }
}
