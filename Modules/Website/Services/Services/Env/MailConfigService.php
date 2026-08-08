<?php
namespace Modules\Admin\Services\Env;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

class MailConfigService
{
    /**
     * Gửi email thử nghiệm với cấu hình runtime
     */
    public function testSendMail(array $config, string $recipient): array
    {
        // Thiết lập cấu hình động vào runtime
        Config::set('mail.mailers.smtp.host', $config['MAIL_HOST']);
        Config::set('mail.mailers.smtp.port', $config['MAIL_PORT']);
        Config::set('mail.mailers.smtp.username', $config['MAIL_USERNAME']);
        Config::set('mail.mailers.smtp.password', $config['MAIL_PASSWORD']);
        Config::set('mail.mailers.smtp.encryption', $config['MAIL_ENCRYPTION']);
        Config::set('mail.from.address', $config['MAIL_FROM_ADDRESS']);
        Config::set('mail.from.name', $config['MAIL_FROM_NAME']);

        try {
            Mail::raw('Đây là email kiểm tra cấu hình từ Admin Panel.', function ($message) use ($recipient, $config) {
                $message->to($recipient)
                        ->subject('Test Email Configuration - ' . $config['MAIL_FROM_NAME']);
            });

            return ['success' => true, 'message' => 'Email đã được gửi thành công đến ' . $recipient];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi gửi mail: ' . $e->getMessage()];
        }
    }
}
