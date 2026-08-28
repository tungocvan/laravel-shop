<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes = 10,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mã xác minh tài khoản INAFO')
            ->greeting('Xin chào '.($notifiable->name ?: 'bạn').',')
            ->line('Mã OTP để xác minh tài khoản của bạn là:')
            ->line($this->code)
            ->line("Mã có hiệu lực trong {$this->expiresInMinutes} phút.")
            ->line('Nếu bạn không yêu cầu đăng ký tài khoản, hãy bỏ qua email này.');
    }
}
