<?php

namespace Modules\Admission\Livewire;

use Livewire\Component;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\SchoolSettingService;

class Search extends Component
{
    public string $MaDinhDanh = '';
    public string $password = '';
    public array $app = [];
    public bool $showModal = false;
    public ?string $message = null;

    public function login(): void
    {
        $this->reset('message', 'showModal', 'app');

        if (! preg_match('/^\d{12}$/', $this->MaDinhDanh)) {
            $this->message = 'Mã định danh phải đúng 12 chữ số.';
            return;
        }

        if (! preg_match('/^\d{8}$/', $this->password)) {
            $this->message = 'Ngày sinh phải đúng 8 chữ số theo định dạng ddmmyyyy. Ví dụ: 01012019.';
            return;
        }

        $application = AdmissionApplication::query()
            ->where('ma_dinh_danh', $this->MaDinhDanh)
            ->first();

        if (! $application) {
            $this->message = 'Không tìm thấy hồ sơ.';
            return;
        }

        $this->app = $application->toArray();

        $birthPassword = $application->ngay_sinh
            ? \Carbon\Carbon::parse($application->ngay_sinh)->format('dmY')
            : null;

        if (! hash_equals((string) $birthPassword, $this->password)) {
            $this->app = [];
            $this->message = 'Thông tin tra cứu không chính xác.';
            return;
        }

        if ($application->status !== 'approved') {
            $this->app = [];
            $this->message = 'Hồ sơ chưa được tiếp nhận hoặc chưa duyệt.';
            return;
        }

        $this->showModal = true;
    }

    public function closeModal(): void
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
