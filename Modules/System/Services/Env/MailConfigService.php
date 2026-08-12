<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailConfigService
{
    public function testSendMail(array $config, string $recipient): array
    {
        $runtimeKeys = [
            'mail.default',
            'mail.mailers.smtp.host',
            'mail.mailers.smtp.port',
            'mail.mailers.smtp.username',
            'mail.mailers.smtp.password',
            'mail.mailers.smtp.encryption',
            'mail.from.address',
            'mail.from.name',
        ];

        $original = [];
        foreach ($runtimeKeys as $key) {
            $original[$key] = Config::get($key);
        }

        try {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $config['MAIL_HOST']);
            Config::set('mail.mailers.smtp.port', (int) $config['MAIL_PORT']);
            Config::set('mail.mailers.smtp.username', $config['MAIL_USERNAME'] ?: null);
            Config::set('mail.mailers.smtp.password', $config['MAIL_PASSWORD'] ?: null);
            Config::set('mail.mailers.smtp.encryption', $config['MAIL_ENCRYPTION'] ?: null);
            Config::set('mail.from.address', $config['MAIL_FROM_ADDRESS']);
            Config::set('mail.from.name', $config['MAIL_FROM_NAME']);

            $this->purgeSmtpMailer();

            Mail::raw('Đây là email kiểm tra cấu hình từ Admin Panel.', function ($message) use ($recipient, $config): void {
                $message->to($recipient)
                    ->subject('Test Email Configuration - '.$config['MAIL_FROM_NAME']);
            });

            return [
                'success' => true,
                'message' => 'Email kiểm tra đã được gửi thành công.',
            ];
        } catch (Throwable $e) {
            Log::warning('SMTP test send failed.', [
                'exception' => $e::class,
                'host' => $config['MAIL_HOST'] ?? null,
                'port' => $config['MAIL_PORT'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email kiểm tra. Vui lòng kiểm tra cấu hình hoặc log hệ thống.',
            ];
        } finally {
            foreach ($original as $key => $value) {
                Config::set($key, $value);
            }

            $this->purgeSmtpMailer();
        }
    }

    private function purgeSmtpMailer(): void
    {
        $manager = app('mail.manager');

        if (method_exists($manager, 'purge')) {
            $manager->purge('smtp');
        }
    }
}
