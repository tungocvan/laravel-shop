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

        $this->info('Captcha GDT đã được tạo. Mở ảnh captcha bằng data bên dưới; dữ liệu này không chứa tài khoản/mật khẩu.');
        $image = $captcha['content'] ?? $captcha['image'] ?? null;
        if (is_string($image) && $image !== '') {
            $this->line($image);
        } else {
            $this->warn('Response captcha không có trường content/image để hiển thị.');
        }

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
