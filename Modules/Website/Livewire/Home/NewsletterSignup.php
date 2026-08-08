<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Models\Newsletter;

class NewsletterSignup extends Component
{
    public $email = '';
    public $subscribed = false;

    // Rules validation
    protected $rules = [
        'email' => 'required|email|unique:newsletters,email',
    ];

    protected $messages = [
        'email.required' => 'Vui lòng nhập email của bạn.',
        'email.email' => 'Email không đúng định dạng.',
        'email.unique' => 'Email này đã được đăng ký rồi.',
    ];

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function subscribe()
    {
        $this->validate();

        // 1. Lưu vào DB
        // Nếu chưa có bảng newsletters, hãy chạy migration
        Newsletter::create(['email' => $this->email]);

        // 2. Giả lập độ trễ mạng để hiển thị loading cho đẹp (UX)
        sleep(1);

        // 3. Hiển thị trạng thái thành công
        $this->subscribed = true;

        // 4. Reset form
        $this->reset('email');
    }

    // Placeholder khi Lazy Load
    public function placeholder()
    {
        return <<<'blade'
        <div class="container mx-auto px-4 mb-20">
            <div class="w-full h-64 bg-gray-100 rounded-3xl animate-pulse"></div>
        </div>
        blade;
    }

    public function render()
    {
        return view('Website::livewire.home.newsletter-signup');
    }
}
