<?php

namespace Modules\Admission\Livewire;

use Livewire\Component;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\SchoolSettingService;

class Search extends Component
{
    public $MaDinhDanh = '';
    public $password = '';
    public $app = [];

    public $showModal = false;
    public $message = null;

    public function mount($ma_dinh_danh = null, $password = null)
    {
        // 👉 nếu không có param => rỗng luôn, không lỗi
        $this->MaDinhDanh = $ma_dinh_danh ?? '';
        $this->password = $password ?? '';
        if (empty($this->ma_dinh_danh) || empty($this->password)) {
            return;
        }


        // 👉 chỉ auto check nếu có đủ dữ liệu
        if (!empty($this->MaDinhDanh) && !empty($this->password)) {
            $this->login();
        }
    }

    public function login()
    {
        $this->reset('message', 'showModal');

        // Validate mã định danh 12 số
        if (!preg_match('/^\d{12}$/', $this->MaDinhDanh)) {
            $this->message = 'Mã định danh phải đúng 12 chữ số.';
            return;
        }

        // Validate password 8 số
        if (!preg_match('/^\d{8}$/', $this->password)) {
            $this->message = 'Mật khẩu phải đúng 6 chữ số (ddmmyy). Ví dụ: 01012019';
            return;
        }

        $this->app = AdmissionApplication::where('ma_dinh_danh', $this->MaDinhDanh)
            ->firstOrFail()
            ->toArray();
        // $application = AdmissionApplication::where('ma_dinh_danh', $this->MaDinhDanh)->first();

        if (!$this->app) {
            $this->message = 'Không tìm thấy hồ sơ.';
            return;
        } 
      // dd($this->app);
        $birthPassword = $this->app['ngay_sinh']
            ? \Carbon\Carbon::parse($this->app['ngay_sinh'])->format('dmY')
            : null;
       //dd($birthPassword, $this->password);
        if ($this->password !== $birthPassword) {
            $this->message = 'Sai mật khẩu. Vui lòng nhập theo ddmmyy.';
            return;
        }

        if ($this->app['status'] === 'approved') {
            $this->showModal = true;
        } else {
            $this->message = 'Hồ sơ chưa được tiếp nhận hoặc chưa duyệt.';
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
    }
    public function render()
    {
        return view('Admission::livewire.search', [
            'schoolSettings' => app(SchoolSettingService::class)->all(),
        ]);
    }
}
