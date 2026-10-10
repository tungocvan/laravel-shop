<?php

namespace Modules\Admission\Livewire\Public;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Admission\Services\AdmissionRegistrationService;

class RegistrationForm extends Component
{
    public $currentStep = 1;

    public $totalSteps = 5;

    public $provinces = [];

    public $tt_wards = [];

    public $ht_wards = [];

    public $noi_sinh_wards = [];

    public $que_quan_wards = [];

    public $noi_dang_ky_khai_sinh_wards = [];

    public $ethnicities = [];

    public $religions = [];

    public array $registrationClasses = [];

    public array $schoolCampuses = [];

    public $copyNoiSinhToQueQuan = false;

    public $sameAddress = false;

    public $applicationId = null;

    public $isEdit = false;

    // Keep PascalCase keys for the existing Blade/service contract.
    public $form = [
        'HoVaTenHocSinh' => '',
        'GioiTinh' => '',
        'NgaySinh' => '',
        'DanToc' => 'Kinh',
        'MaDinhDanh' => '',
        'QuocTich' => 'Việt Nam',
        'TonGiao' => 'Không',
        'SDTEnetViet' => '',
        'NoiSinh' => '',
        'NoiSinhPx' => '',
        'NoiSinhTt' => '',
        'NoiSinhChiTiet' => '',
        'NoiDangKyKhaiSinhPx' => '',
        'NoiDangKyKhaiSinhTt' => '',
        'QueQuan' => '',
        'QueQuanPx' => '',
        'QueQuanTt' => '',
        'TTSN' => '',
        'TTD' => '',
        'TTKP' => '',
        'TTPX' => '',
        'TTTTP' => '',
        'HTSN' => '',
        'HTD' => '',
        'HTKP' => '',
        'HTPX' => '',
        'HTTTP' => '',
        'OChungVoi' => '',
        'QuanHeNguoiNuoiDuong' => '',
        'ConThu' => '',
        'TSAnhChiEm' => '',
        'HoanThanhLopLa' => 'Có',
        'TruongMamNon' => '',
        'KhaNangHocSinh' => [],
        'SucKhoeCanLuuY' => [],
        'SucKhoeKhac' => '',
        'HoTenCha' => '',
        'NamSinhCha' => '',
        'TdvhCha' => '',
        'TdcmCha' => '',
        'NgheNghiepCha' => 'LĐTD',
        'ChucVuCha' => '',
        'DienThoaiCha' => '',
        'CCCDCha' => '',
        'HoTenMe' => '',
        'NamSinhMe' => '',
        'TdvhMe' => '',
        'TdcmMe' => '',
        'NgheNghiepMe' => 'LĐTD',
        'ChucVuMe' => '',
        'DienThoaiMe' => '',
        'CCCDMe' => '',
        'HoTenNguoiGiamHo' => '',
        'QuanHeGiamHo' => '',
        'DienThoaiGiamHo' => '',
        'CCCDGiamHo' => '',
        'LoaiLopDangKy' => 'Lớp thường',
        'CK_GocHocTap' => true,
        'CK_SachVo' => true,
        'CK_HopPH' => true,
        'CK_ThamGiaHD' => true,
        'CK_GanGui' => true,
        'Lop' => '',
        'Gvcn' => '',
        'BaoMau' => '',
        'SchoolCampusName' => '',
        'SchoolCampusAddress' => '',
        'NgayLamDon' => '',
        'NguoiLamDon' => '',
        'SoSoTiemChung' => '',
    ];

    public function mount($id = null): void
    {
        $service = app(AdmissionRegistrationService::class);
        $options = $service->options();

        $this->provinces = $options['provinces'];
        $this->ethnicities = $options['ethnicities'];
        $this->religions = $options['religions'];
        $this->registrationClasses = $options['registrationClasses'];
        $this->schoolCampuses = $options['schoolCampuses'];
        $this->form['LoaiLopDangKy'] = $this->registrationClasses[0] ?? '';
        $this->form['NgayLamDon'] = now()->format('Y-m-d');

        if (! $id) {
            return;
        }

        $this->authorizeAdmin('edit_admission');
        $this->isEdit = true;
        $this->applicationId = (int) $id;

        $application = $service->findForEdit($this->applicationId);

        if ($application->loai_lop_dang_ky && ! in_array($application->loai_lop_dang_ky, $this->registrationClasses, true)) {
            $this->registrationClasses[] = $application->loai_lop_dang_ky;
        }

        $this->form = $service->toForm($application);
        $this->loadWardLists();
    }

    protected function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'form.HoVaTenHocSinh' => ['required', 'string', 'min:5', 'max:255'],
            'form.GioiTinh' => ['nullable', Rule::in(['Nam', 'Nữ'])],
            'form.NgaySinh' => ['nullable', 'date'],
            'form.DanToc' => ['nullable', 'string', 'max:100'],
            'form.MaDinhDanh' => ['required', 'digits:12'],
            'form.QuocTich' => ['nullable', 'string', 'max:100'],
            'form.TonGiao' => ['nullable', 'string', 'max:100'],
            'form.SDTEnetViet' => ['nullable', 'regex:/^\d{8,15}$/'],
            'form.NoiSinh' => ['nullable', 'string', 'max:255'],
            'form.NoiSinhPx' => ['nullable', 'string', 'max:255'],
            'form.NoiSinhTt' => ['nullable', 'string', 'max:255'],
            'form.NoiSinhChiTiet' => ['nullable', 'string', 'max:255'],
            'form.NoiDangKyKhaiSinhPx' => ['nullable', 'string', 'max:255'],
            'form.NoiDangKyKhaiSinhTt' => ['nullable', 'string', 'max:255'],
            'form.QueQuan' => ['nullable', 'string', 'max:255'],
            'form.QueQuanPx' => ['nullable', 'string', 'max:255'],
            'form.QueQuanTt' => ['nullable', 'string', 'max:255'],
            'form.TTSN' => ['nullable', 'string', 'max:100'],
            'form.TTD' => ['nullable', 'string', 'max:255'],
            'form.TTKP' => ['nullable', 'string', 'max:255'],
            'form.TTPX' => ['nullable', 'string', 'max:255'],
            'form.TTTTP' => ['nullable', 'string', 'max:255'],
            'form.HTSN' => ['nullable', 'string', 'max:100'],
            'form.HTD' => ['nullable', 'string', 'max:255'],
            'form.HTKP' => ['nullable', 'string', 'max:255'],
            'form.HTPX' => ['nullable', 'string', 'max:255'],
            'form.HTTTP' => ['nullable', 'string', 'max:255'],
            'form.OChungVoi' => ['nullable', 'string', 'max:255'],
            'form.QuanHeNguoiNuoiDuong' => ['nullable', 'string', 'max:255'],
            'form.ConThu' => ['nullable', 'integer', 'min:0', 'max:30'],
            'form.TSAnhChiEm' => ['nullable', 'integer', 'min:0', 'max:30'],
            'form.HoanThanhLopLa' => ['nullable', 'string', 'max:100'],
            'form.TruongMamNon' => ['nullable', 'string', 'max:255'],
            'form.KhaNangHocSinh' => ['array'],
            'form.KhaNangHocSinh.*' => ['string', 'max:255'],
            'form.SucKhoeCanLuuY' => ['array'],
            'form.SucKhoeCanLuuY.*' => ['string', 'max:255'],
            'form.SucKhoeKhac' => ['nullable', 'string', 'max:255'],
            'form.HoTenCha' => ['nullable', 'string', 'max:255'],
            'form.NamSinhCha' => ['nullable', 'integer', 'min:1900', 'max:'.$currentYear],
            'form.TdvhCha' => ['nullable', 'string', 'max:100'],
            'form.TdcmCha' => ['nullable', 'string', 'max:100'],
            'form.NgheNghiepCha' => ['nullable', 'string', 'max:255'],
            'form.ChucVuCha' => ['nullable', 'string', 'max:255'],
            'form.DienThoaiCha' => ['nullable', 'regex:/^\d{8,15}$/'],
            'form.CCCDCha' => ['nullable', 'regex:/^\d{9,12}$/'],
            'form.HoTenMe' => ['nullable', 'string', 'max:255'],
            'form.NamSinhMe' => ['nullable', 'integer', 'min:1900', 'max:'.$currentYear],
            'form.TdvhMe' => ['nullable', 'string', 'max:100'],
            'form.TdcmMe' => ['nullable', 'string', 'max:100'],
            'form.NgheNghiepMe' => ['nullable', 'string', 'max:255'],
            'form.ChucVuMe' => ['nullable', 'string', 'max:255'],
            'form.DienThoaiMe' => ['nullable', 'regex:/^\d{8,15}$/'],
            'form.CCCDMe' => ['nullable', 'regex:/^\d{9,12}$/'],
            'form.HoTenNguoiGiamHo' => ['nullable', 'string', 'max:255'],
            'form.QuanHeGiamHo' => ['nullable', 'string', 'max:255'],
            'form.DienThoaiGiamHo' => ['nullable', 'regex:/^\d{8,15}$/'],
            'form.CCCDGiamHo' => ['nullable', 'regex:/^\d{9,12}$/'],
            'form.LoaiLopDangKy' => ['required', 'string', 'max:255', Rule::in($this->registrationClasses)],
            'form.CK_GocHocTap' => ['boolean'],
            'form.CK_SachVo' => ['boolean'],
            'form.CK_HopPH' => ['boolean'],
            'form.CK_ThamGiaHD' => ['boolean'],
            'form.CK_GanGui' => ['boolean'],
            'form.Lop' => ['nullable', 'string', 'max:100'],
            'form.Gvcn' => ['nullable', 'string', 'max:255'],
            'form.BaoMau' => ['nullable', 'string', 'max:255'],
            'form.SchoolCampusName' => ['nullable', 'required_with:form.Lop', 'string', 'max:255', Rule::in(array_column($this->schoolCampuses, 'name'))],
            'form.SchoolCampusAddress' => ['nullable', 'required_with:form.SchoolCampusName', 'string', 'max:500'],
            'form.NgayLamDon' => ['nullable', 'date'],
            'form.NguoiLamDon' => ['nullable', 'string', 'max:255'],
            'form.SoSoTiemChung' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function setStep($step): void
    {
        $target = max(1, min($this->totalSteps, (int) $step));

        // Admin edit is a review workspace: allow direct navigation between all
        // steps and validate the complete record only when Save is requested.
        if ($this->isEdit) {
            $this->currentStep = $target;

            return;
        }

        if ($target > $this->currentStep) {
            if (! $this->validateStepOrNotify((int) $this->currentStep)) {
                return;
            }

            $target = min($target, $this->currentStep + 1);
        }

        $this->currentStep = $target;
    }

    public function updated($field): void
    {
        $service = app(AdmissionRegistrationService::class);

        $map = [
            'form.TTTTP' => 'tt_wards',
            'form.HTTTP' => 'ht_wards',
            'form.NoiSinhTt' => 'noi_sinh_wards',
            'form.NoiDangKyKhaiSinhTt' => 'noi_dang_ky_khai_sinh_wards',
            'form.QueQuanTt' => 'que_quan_wards',
        ];

        if (isset($map[$field])) {
            $province = $this->form[str_replace('form.', '', $field)] ?? '';
            $this->{$map[$field]} = $service->wardsForProvince($province);

            // A ward from the previous province must never be submitted under
            // a newly selected province. Initial edit hydration is unaffected.
            $wardFields = [
                'form.TTTTP' => 'TTPX',
                'form.HTTTP' => 'HTPX',
                'form.NoiSinhTt' => 'NoiSinhPx',
                'form.NoiDangKyKhaiSinhTt' => 'NoiDangKyKhaiSinhPx',
                'form.QueQuanTt' => 'QueQuanPx',
            ];
            $this->form[$wardFields[$field]] = '';
        }
    }

    public function updatedCopyNoiSinhToQueQuan($value): void
    {
        if (! $value) {
            return;
        }

        $this->form['QueQuanPx'] = $this->form['NoiSinhPx'];
        $this->form['QueQuanTt'] = $this->form['NoiSinhTt'];
        $this->que_quan_wards = $this->noi_sinh_wards;
    }

    public function updatedSameAddress($value): void
    {
        if (! $value) {
            return;
        }

        $this->form['HTSN'] = $this->form['TTSN'];
        $this->form['HTD'] = $this->form['TTD'];
        $this->form['HTKP'] = $this->form['TTKP'];
        $this->form['HTTTP'] = $this->form['TTTTP'];
        $this->form['HTPX'] = $this->form['TTPX'];
        $this->ht_wards = $this->tt_wards;
    }

    public function updatedForm($value, $key): void
    {
        if ($key === 'SchoolCampusName') {
            $campus = collect($this->schoolCampuses)->firstWhere('name', $value);
            $this->form['SchoolCampusAddress'] = $campus['address'] ?? '';

            return;
        }

        if (! $this->sameAddress) {
            return;
        }

        $map = [
            'TTSN' => 'HTSN',
            'TTD' => 'HTD',
            'TTKP' => 'HTKP',
            'TTTTP' => 'HTTTP',
            'TTPX' => 'HTPX',
        ];

        if (isset($map[$key])) {
            $this->form[$map[$key]] = $value;
        }

    }

    public function nextStep(): void
    {
        if ($this->currentStep >= $this->totalSteps) {
            return;
        }

        if ($this->validateStepOrNotify((int) $this->currentStep)) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function save(AdmissionRegistrationService $service): void
    {
        $this->authorizeAdmin($this->isEdit ? 'edit_admission' : 'create_admission');

        for ($step = 1; $step <= $this->totalSteps; $step++) {
            if (! $this->validateStepOrNotify($step)) {
                return;
            }
        }

        try {
            $data = $this->form;
            $data['KhaNangHocSinh'] = is_array($data['KhaNangHocSinh'] ?? null) ? $data['KhaNangHocSinh'] : [];
            $data['SucKhoeCanLuuY'] = is_array($data['SucKhoeCanLuuY'] ?? null) ? $data['SucKhoeCanLuuY'] : [];

            if (! empty($data['SucKhoeKhac'])) {
                $data['SucKhoeCanLuuY'][] = trim((string) $data['SucKhoeKhac']);
            }

            if (($data['OChungVoi'] ?? null) === 'other') {
                $data['OChungVoi'] = $data['QuanHeNguoiNuoiDuong'] ?? null;
            }

            if ($this->isEdit) {
                $application = $service->update((int) $this->applicationId, $data);
                $redirectUrl = route('admin.admission.index');
            } else {
                $application = $service->create($data);
                $redirectUrl = route('admission.register');
            }

            $this->dispatch('show-success-modal', [
                'name' => $application->ho_va_ten_hoc_sinh,
                'redirectUrl' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('Admission registration save failed.', [
                'application_id' => $this->isEdit ? (int) $this->applicationId : null,
                'mode' => $this->isEdit ? 'edit' : 'create',
                'exception' => $e::class,
            ]);

            session()->flash('error', 'Có lỗi xảy ra khi lưu hồ sơ. Vui lòng thử lại.');
        }
    }

    public function render()
    {
        return view('Admission::livewire.admission.registration-form');
    }

    public function goToValidationStep(int $step): void
    {
        $this->currentStep = max(1, min($this->totalSteps, $step));
        $this->dispatch('admission-validation-step-opened');
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute là thông tin bắt buộc.',
            'required_with' => ':attribute là thông tin bắt buộc khi đã thực hiện sắp xếp lớp.',
            'string' => ':attribute phải là nội dung hợp lệ.',
            'min' => ':attribute chưa đạt độ dài tối thiểu.',
            'max' => ':attribute vượt quá độ dài cho phép.',
            'digits' => ':attribute phải gồm đúng :digits chữ số.',
            'integer' => ':attribute phải là số nguyên.',
            'date' => ':attribute không đúng định dạng ngày.',
            'regex' => ':attribute không đúng định dạng.',
            'in' => ':attribute không nằm trong danh sách cho phép.',
            'array' => ':attribute có dữ liệu không hợp lệ.',
            'boolean' => ':attribute có giá trị không hợp lệ.',
            'distinct' => ':attribute không được trùng nhau.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.HoVaTenHocSinh' => 'Họ và tên học sinh',
            'form.MaDinhDanh' => 'Mã định danh',
            'form.SDTEnetViet' => 'Số điện thoại eNetViet',
            'form.LoaiLopDangKy' => 'Lớp đăng ký',
            'form.SchoolCampusName' => 'Cơ sở trường',
            'form.SchoolCampusAddress' => 'Địa chỉ cơ sở trường',
            'form.NgayLamDon' => 'Ngày làm đơn',
            'form.NguoiLamDon' => 'Người làm đơn',
            'form.SoSoTiemChung' => 'Số sổ tiêm chủng',
            'form.DienThoaiCha' => 'Điện thoại cha',
            'form.DienThoaiMe' => 'Điện thoại mẹ',
            'form.DienThoaiNguoiGiamHo' => 'Điện thoại người giám hộ',
            'form.CCCDCha' => 'CCCD cha',
            'form.CCCDMe' => 'CCCD mẹ',
            'form.CCCDGiamHo' => 'CCCD người giám hộ',
        ];
    }

    private function validateCurrentStep(): void
    {
        $this->validate($this->rulesForStep((int) $this->currentStep));
    }

    private function validateStepOrNotify(int $step): bool
    {
        try {
            $this->validate($this->rulesForStep($step));

            return true;
        } catch (ValidationException $e) {
            $stepNames = [
                1 => 'Thông tin học sinh',
                2 => 'Địa chỉ cư trú',
                3 => 'Thông tin bổ sung',
                4 => 'Thông tin cha, mẹ và người giám hộ',
                5 => 'Xác nhận và sắp xếp lớp',
            ];

            $errors = $e->validator->errors()->all();
            $details = collect($errors)->take(5)->implode(' • ');

            $this->dispatch('show-validation-modal', [
                'step' => $step,
                'stepName' => $stepNames[$step] ?? 'Thông tin hồ sơ',
                'message' => $details !== ''
                    ? 'Cần cập nhật: '.$details
                    : 'Hồ sơ còn thông tin thiếu hoặc chưa hợp lệ. Vui lòng quay lại bước này để kiểm tra.',
            ]);

            return false;
        }
    }

    private function rulesForStep(int $step): array
    {
        $allRules = $this->rules();
        $fields = match ($step) {
            1 => ['HoVaTenHocSinh', 'GioiTinh', 'NgaySinh', 'DanToc', 'MaDinhDanh', 'QuocTich', 'TonGiao', 'SDTEnetViet', 'NoiSinh', 'NoiSinhPx', 'NoiSinhTt', 'NoiSinhChiTiet', 'NoiDangKyKhaiSinhPx', 'NoiDangKyKhaiSinhTt', 'QueQuan', 'QueQuanPx', 'QueQuanTt'],
            2 => ['TTSN', 'TTD', 'TTKP', 'TTPX', 'TTTTP', 'HTSN', 'HTD', 'HTKP', 'HTPX', 'HTTTP'],
            3 => ['OChungVoi', 'QuanHeNguoiNuoiDuong', 'ConThu', 'TSAnhChiEm', 'HoanThanhLopLa', 'TruongMamNon', 'KhaNangHocSinh', 'SucKhoeCanLuuY', 'SucKhoeKhac'],
            4 => ['HoTenCha', 'NamSinhCha', 'TdvhCha', 'TdcmCha', 'NgheNghiepCha', 'ChucVuCha', 'DienThoaiCha', 'CCCDCha', 'HoTenMe', 'NamSinhMe', 'TdvhMe', 'TdcmMe', 'NgheNghiepMe', 'ChucVuMe', 'DienThoaiMe', 'CCCDMe', 'HoTenNguoiGiamHo', 'QuanHeGiamHo', 'DienThoaiGiamHo', 'CCCDGiamHo'],
            5 => ['LoaiLopDangKy', 'CK_GocHocTap', 'CK_SachVo', 'CK_HopPH', 'CK_ThamGiaHD', 'CK_GanGui', 'Lop', 'Gvcn', 'BaoMau', 'SchoolCampusName', 'SchoolCampusAddress', 'NgayLamDon', 'NguoiLamDon', 'SoSoTiemChung'],
            default => [],
        };

        return collect($allRules)
            ->filter(fn ($rule, $key) => collect($fields)->contains(fn ($field) => $key === "form.{$field}" || str_starts_with($key, "form.{$field}.")))
            ->all();
    }

    private function loadWardLists(): void
    {
        $service = app(AdmissionRegistrationService::class);

        $this->tt_wards = $service->wardsForProvince($this->form['TTTTP'] ?? null);
        $this->ht_wards = $service->wardsForProvince($this->form['HTTTP'] ?? null);
        $this->noi_sinh_wards = $service->wardsForProvince($this->form['NoiSinhTt'] ?? null);
        $this->que_quan_wards = $service->wardsForProvince($this->form['QueQuanTt'] ?? null);
        $this->noi_dang_ky_khai_sinh_wards = $service->wardsForProvince($this->form['NoiDangKyKhaiSinhTt'] ?? null);
    }

    private function authorizeAdmin(string $permission): int
    {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin && $admin->can($permission), 403);

        return (int) $admin->getAuthIdentifier();
    }
}
