# cấu hình .env
# Cấu hình email
# lấy mậ khẩu ứng dụng điền vào MAIL_PASSWORD
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tungocvan2@gmail.com
MAIL_PASSWORD="rmbi.... ynbt" 
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tungocvan2@gmail.com
MAIL_FROM_NAME=Laravel
MAIL_LOG_CHANNEL=stack

# sử dụng tinker để test email:
php artisan tinker
> Mail::raw('Test Gmail', function ($m) {$m->to('tungocvan@gmail.com')->subject('Test từ Laravel');});

# test hàng đợi
# cấu hình .env
QUEUE_CONNECTION=database 
php artisan make:mail TestQueueMail
```php
<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class TestQueueMail extends Mailable implements ShouldQueue
{
    public function build()
    {
        return $this->subject('Test Queue')
                    ->text('emails.test');
    }
}

tạo blade: resources/views/emails/test.blade.php
Test Gmail gửi qua Queue 🚀
```

php artisan tinker
> use App\Mail\TestQueueMail;
> use Illuminate\Support\Facades\Mail;
> Mail::to('tungocvan@gmail.com')->queue(new TestQueueMail());

# lưu ý phải chạy queue trước khi test:
./run-queue.sh