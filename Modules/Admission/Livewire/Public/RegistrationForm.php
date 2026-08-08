<?php

namespace Modules\Admission\Livewire\Public;

use Carbon\Carbon;
use Livewire\Component;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\AdmissionCatalog;
use Modules\Admission\Models\AdmissionLocation;
use Modules\Admission\Services\AdmissionService;
use Modules\Admission\Services\SchoolSettingService;

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

    public $copyNoiSinhToQueQuan = false;

    public $sameAddress = false;

    public $applicationId = null;

    public $isEdit = false;

    // ⚠️ QUAN TRỌNG: GIỮ NGUYÊN KEY PascalCase
    public $form = [

        // STEP 1
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
        // STEP 2
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

        // STEP 3
        'OChungVoi' => '',
        'QuanHeNguoiNuoiDuong' => '',
        'ConThu' => '',
        'TSAnhChiEm' => '',
        'HoanThanhLopLa' => 'Có',
        'TruongMamNon' => '',
        'KhaNangHocSinh' => [],
        'SucKhoeCanLuuY' => [],
        'SucKhoeKhac' => '',

        // STEP 4
        'HoTenCha' => '',
        'NamSinhCha' => '',
        'NgheNghiepCha' => 'LĐTD',
        'ChucVuCha' => '',
        'DienThoaiCha' => '',
        'CCCDCha' => '',

        'HoTenMe' => '',
        'NamSinhMe' => '',
        'NgheNghiepMe' => 'LĐTD',
        'ChucVuMe' => '',
        'DienThoaiMe' => '',
        'CCCDMe' => '',

        'HoTenNguoiGiamHo' => '',
        'QuanHeGiamHo' => '',
        'DienThoaiGiamHo' => '',
        'CCCDGiamHo' => '',

        // STEP 5
        'LoaiLopDangKy' => 'Lớp thường',
        'CK_GocHocTap' => true,
        'CK_SachVo' => true,
        'CK_HopPH' => true,
        'CK_ThamGiaHD' => true,
        'CK_GanGui' => true,

        'Lop' => '',
        'Gvcn' => '',
        'BaoMau' => '',

        'NgayLamDon' => '',
        'NguoiLamDon' => '',
    ];

    //     public $form = [
    //         // STEP 1
    //         'HoVaTenHocSinh' => 'Nguyễn Minh An',
    //         'GioiTinh' => 'Nam',
    //         'NgaySinh' => '2020-01-01',
    //         'DanToc' => 'Kinh',
    //         'MaDinhDanh' => '079120000001',
    //         'QuocTich' => 'Việt Nam',
    //         'TonGiao' => 'Không',
    //         'SDTEnetViet' => '0908123456',

    //         'NoiSinh' => '',
    //         'NoiSinhPx'  => '',
    //         'NoiSinhTt'  => '',
    //         'NoiSinhChiTiet'   => '',
    //         'NoiDangKyKhaiSinhPx' => '',
    //         'NoiDangKyKhaiSinhTt' => '',
    //         'QueQuan' => '',
    //         'QueQuanPx' => '',
    //         'QueQuanTt' => '',

    //         // STEP 2
    //         'TTSN' => '45',
    //         'TTD' => 'Huỳnh Tấn Phát',
    //         'TTKP' => 'KP 2',
    //         'TTPX' => '',
    //         'TTTTP' => '',

    //         'HTSN' => '45',
    //         'HTD' => 'Huỳnh Tấn Phát',
    //         'HTKP' => 'KP 2',
    //         'HTPX' => '',
    //         'HTTTP' => '',

    //         // STEP 3
    //         'OChungVoi' => 'Cha mẹ',
    //         'QuanHeNguoiNuoiDuong' => '',
    //         'ConThu' => '1',
    //         'TSAnhChiEm' => '2',
    //         'HoanThanhLopLa' => 'Có',
    //         'TruongMamNon' => 'Rạng Đông',
    //         'KhaNangHocSinh' => [],
    //         'SucKhoeCanLuuY' => [],
    //         'SucKhoeKhac' => '',

    //         // STEP 4
    //         'HoTenCha' => 'Nguyễn Văn Hùng',
    //         'NamSinhCha' => '1990',
    //         'NgheNghiepCha' => 'LĐTD',
    //         'ChucVuCha' => '',
    //         'DienThoaiCha' => '0909000001',
    //         'CCCDCha' => '079088880001
    // ',

    //         'HoTenMe' => 'Trần Thị Mai',
    //         'NamSinhMe' => '1992',
    //         'NgheNghiepMe' => 'LĐTD',
    //         'ChucVuMe' => '',
    //         'DienThoaiMe' => '0909000002',
    //         'CCCDMe' => '079088880002',

    //         'HoTenNguoiGiamHo' => '',
    //         'QuanHeGiamHo' => '',
    //         'DienThoaiGiamHo' => '',
    //         'CCCDGiamHo' => '',

    //         // STEP 5
    //         'LoaiLopDangKy' => 'Lớp thường',
    //         'CK_GocHocTap' => true,
    //         'CK_SachVo' => true,
    //         'CK_HopPH' => true,
    //         'CK_ThamGiaHD' => true,
    //         'CK_GanGui' => true,
    // 'Lop' => '',
    // 'Gvcn' => '',
    // 'BaoMau' => '',
    //         'NgayLamDon' => '',
    //         'NguoiLamDon' => 'Trần Thị Mai',
    //     ];

    protected $rules = [
        'form.HoVaTenHocSinh' => 'required|min:5',
        'form.MaDinhDanh' => 'required|digits:12',
        'form.LoaiLopDangKy' => 'required|string|max:255',
    ];

    // public function mount()
    // {
    //     $this->provinces = AdmissionLocation::select('province_name')->distinct()->get()->toArray();
    //     $this->ethnicities = AdmissionCatalog::where('type', 'ethnicity')->get()->toArray();
    //     $this->religions = AdmissionCatalog::where('type', 'religion')->get()->toArray();

    //     $this->form['NgayLamDon'] = date('Y-m-d');
    // }

    public function mount($id = null)
    {
        // Load danh mục (giữ nguyên)
        $this->provinces = AdmissionLocation::select('province_name')->distinct()->get()->toArray();
        $this->ethnicities = AdmissionCatalog::where('type', 'ethnicity')->get()->toArray();
        $this->religions = AdmissionCatalog::where('type', 'religion')->get()->toArray();
        $this->registrationClasses = app(SchoolSettingService::class)->registrationClasses();
        $this->form['LoaiLopDangKy'] = $this->registrationClasses[0] ?? '';

        $this->form['NgayLamDon'] = date('Y-m-d');
        // ================= EDIT MODE =================
        if ($id) {

            $this->isEdit = true;
            $this->applicationId = $id;

            $app = AdmissionApplication::findOrFail($id);

            if ($app->loai_lop_dang_ky && ! in_array($app->loai_lop_dang_ky, $this->registrationClasses, true)) {
                $this->registrationClasses[] = $app->loai_lop_dang_ky;
            }
            // dump($app->kha_nang_hoc_sinh, gettype($app->kha_nang_hoc_sinh));
            // dd($this->form['KhaNangHocSinh']);
            // dd($app->gioi_tinh);
            // ⚠️ MAP DB → FORM (phải đúng key Service)
            $this->form = [
                // STEP 1
                'HoVaTenHocSinh' => $app->ho_va_ten_hoc_sinh,
                'Status' => $app->status,
                'GioiTinh' => $app->gioi_tinh,
                'NgaySinh' => $app->ngay_sinh ? Carbon::parse($app->ngay_sinh)->format('Y-m-d') : '',
                'DanToc' => $app->dan_toc ?? 'Kinh',
                'MaDinhDanh' => $app->ma_dinh_danh,
                'QuocTich' => $app->quoc_tich ?? 'Việt Nam',
                'TonGiao' => $app->ton_giao ?? 'Không',
                'SDTEnetViet' => $app->sdt_enetviet,
                'NoiSinh' => $app->noi_sinh,
                'NoiSinhPx' => $app->noi_sinh_px,
                'NoiSinhTt' => $app->noi_sinh_tt,
                'NoiSinhChiTiet' => $app->noi_sinh_chi_tiet,
                'NoiDangKyKhaiSinhPx' => $app->noi_dang_ky_khai_sinh_px,
                'NoiDangKyKhaiSinhTt' => $app->noi_dang_ky_khai_sinh_tt,
                'QueQuan' => $app->que_quan,
                'QueQuanPx' => $app->que_quan_px,
                'QueQuanTt' => $app->que_quan_tt,
                // STEP 2
                'TTSN' => $app->ttsn,
                'TTD' => $app->ttd,
                'TTKP' => $app->ttkp,
                'TTPX' => $app->ttpx,
                'TTTTP' => $app->ttttp,
                'HTSN' => $app->htsn,
                'HTD' => $app->htd,
                'HTKP' => $app->htkp,
                'HTPX' => $app->htpx,
                'HTTTP' => $app->htttp,

                // STEP 3
                'OChungVoi' => $app->o_chung_voi,
                'QuanHeNguoiNuoiDuong' => $app->quan_he_nguoi_nuoi_duong,
                'ConThu' => $app->con_thu,
                'TSAnhChiEm' => $app->ts_anh_chi_em,
                'HoanThanhLopLa' => $app->hoan_thanh_lop_la ?? 'Có',
                'TruongMamNon' => $app->truong_mam_non,

                // ⚠️ STRING → ARRAY
                'KhaNangHocSinh' => is_array($app->kha_nang_hoc_sinh)
                    ? $app->kha_nang_hoc_sinh
                    : [],
                'SucKhoeCanLuuY' => is_array($app->suc_khoe_can_luu_y)
                    ? $app->suc_khoe_can_luu_y
                    : [],

                // STEP 4
                'HoTenCha' => $app->ho_ten_cha ?? '',
                'NamSinhCha' => $app->nam_sinh_cha ?? '',
                'TdvhCha' => $app->tdvh_cha ?? '',
                'TdcmCha' => $app->tdcm_cha ?? '',
                'NgheNghiepCha' => $app->nghe_nghiep_cha ?? 'LĐTD',
                'ChuvuCha' => $app->chuc_vu_cha ?? '',
                'DienThoaiCha' => $app->dien_thoai_cha ?? '',
                'CCCDCha' => $app->cccd_cha ?? '',
                'HoTenMe' => $app->ho_ten_me ?? '',
                'NamSinhMe' => $app->nam_sinh_me ?? '',
                'TdvhMe' => $app->tdvh_me ?? '',
                'TdcmMe' => $app->tdcm_me ?? '',
                'NgheNghiepMe' => $app->nghe_nghiep_me ?? 'LĐTD',
                'ChuvuMe' => $app->chuc_vu_me ?? '',
                'DienThoaiMe' => $app->dien_thoai_me ?? '',
                'CCCDMe' => $app->cccd_me ?? '',
                'HoTenNguoiGiamHo' => $app->ho_ten_nguoi_giam_ho ?? $app->ho_ten_me ?? '',
                'DienThoaiGiamHo' => $app->dien_thoai_giam_ho ?? $app->dien_thoai_me ?? '',
                'CCCDGiamHo' => $app->cccd_giam_ho ?? $app->cccd_me ?? '',

                // STEP 5
                'LoaiLopDangKy' => $app->loai_lop_dang_ky ?? 'Lớp thường',

                'CK_GocHocTap' => $app->ck_goc_hoc_tap ? (bool) $app->ck_goc_hoc_tap : true,
                'CK_SachVo' => $app->ck_sach_vo ? (bool) $app->ck_sach_vo : true,
                'CK_HopPH' => $app->ck_hop_ph ? (bool) $app->ck_hop_ph : true,
                'CK_ThamGiaHD' => $app->ck_tham_gia_hd ? (bool) $app->ck_tham_gia_hd : true,
                'CK_GanGui' => $app->ck_gan_gui ? (bool) $app->ck_gan_gui : true,

                'Lop' => $app->lop ?? '',
                'Gvcn' => $app->gvcn ?? '',
                'BaoMau' => $app->bao_mau ?? '',

                'NguoiLamDon' => $app->nguoi_lam_don ?? '',
            ];

            // Load wards nếu có
            $this->updated('form.TTTTP');
            $this->updated('form.HTTTP');
            $this->updated('form.NoiSinhTt');
            $this->updated('form.QueQuanTt');
            $this->updated('form.NoiDangKyKhaiSinhTt');
        }
    }

    public function setStep($step)
    {
        $this->currentStep = $step;
    }

    public function updated($field)
    {
        if ($field === 'form.TTTTP') {
            $this->tt_wards = AdmissionLocation::where('province_name', $this->form['TTTTP'])->get()->toArray();
        }

        if ($field === 'form.HTTTP') {
            $this->ht_wards = AdmissionLocation::where('province_name', $this->form['HTTTP'])->get()->toArray();
        }

        if ($field === 'form.NoiSinhTt') {
            $this->noi_sinh_wards = AdmissionLocation::where('province_name', $this->form['NoiSinhTt'])->get()->toArray();
        }

        if ($field === 'form.NoiDangKyKhaiSinhTt') {
            $this->noi_dang_ky_khai_sinh_wards = AdmissionLocation::where('province_name', $this->form['NoiDangKyKhaiSinhTt'])->get()->toArray();
        }

        if ($field === 'form.QueQuanTt') {
            $this->que_quan_wards = AdmissionLocation::where('province_name', $this->form['QueQuanTt'])->get()->toArray();
        }
    }

    public function updatedCopyNoiSinhToQueQuan($value)
    {
        if ($value) {

            $this->form['QueQuanPx'] = $this->form['NoiSinhPx'];
            $this->form['QueQuanTt'] = $this->form['NoiSinhTt'];
        }
    }
    // ================= SAME ADDRESS =================

    public function updatedSameAddress($value)
    {
        if ($value) {
            $this->form['HTSN'] = $this->form['TTSN'];
            $this->form['HTD'] = $this->form['TTD'];
            $this->form['HTKP'] = $this->form['TTKP'];
            $this->form['HTTTP'] = $this->form['TTTTP'];
            $this->form['HTPX'] = $this->form['TTPX'];

            $this->ht_wards = $this->tt_wards;
        }
    }

    public function updatedForm($value, $key)
    {
        if ($this->sameAddress) {

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
    }

    // ================= STEP =================

    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    // ================= SAVE =================

    // public function save(AdmissionService $service)
    // {
    //     $this->validate();

    //     try {

    //         // ⚠️ CLONE DATA (QUAN TRỌNG)
    //         $data = $this->form;

    //         // ===== STEP 3 =====

    //         // Khả năng
    //         $data['KhaNangHocSinh'] = !empty($data['KhaNangHocSinh'])
    //             ? implode(', ', $data['KhaNangHocSinh'])
    //             : null;

    //         // Sức khỏe
    //         $health = $data['SucKhoeCanLuuY'] ?? [];

    //         if (!empty($data['SucKhoeKhac'])) {
    //             $health[] = $data['SucKhoeKhac'];
    //         }

    //         $data['SucKhoeCanLuuY'] = !empty($health)
    //             ? implode(', ', $health)
    //             : null;

    //         // Người nuôi dưỡng
    //         if (($data['OChungVoi'] ?? null) === 'other') {
    //             $data['OChungVoi'] = $data['QuanHeNguoiNuoiDuong'] ?? null;
    //         }

    //         // Trim
    //         $data = array_map(fn($v) => is_string($v) ? trim($v) : $v, $data);

    //         // SAVE
    //         $application = $service->createRegistration($data);

    //         if ($application && $application->id) {

    //             $this->dispatch('show-success-modal', [
    //                 'name' => $application->ho_va_ten_hoc_sinh,
    //                 'redirectUrl' => route('admission.register')
    //             ]);
    //         }
    //     } catch (\Exception $e) {

    //         \Log::error("Lỗi lưu đơn: " . $e->getMessage());

    //         session()->flash('error', 'Có lỗi xảy ra khi lưu.');
    //     }
    // }

    public function save(AdmissionService $service)
    {
        $this->validate();

        try {
            $data = $this->form;

            // FORMAT giống bạn đang làm
            $data['KhaNangHocSinh'] = is_array($data['KhaNangHocSinh'])
                ? $data['KhaNangHocSinh']
                : [];

            $data['SucKhoeCanLuuY'] = is_array($data['SucKhoeCanLuuY'])
                ? $data['SucKhoeCanLuuY']
                : [];

            if (($data['OChungVoi'] ?? null) === 'other') {
                $data['OChungVoi'] = $data['QuanHeNguoiNuoiDuong'] ?? null;
            }

            // ================= EDIT / CREATE =================
            if ($this->isEdit) {

                if ($data['Lop'] !== '' && $data['Gvcn'] !== '') {
                    $data['Status'] = 'approved';
                } else {
                    $data['Status'] = 'pending';
                }

                $application = $service->updateRegistration($this->applicationId, $data);
                $this->dispatch('show-success-modal', [
                    'name' => $application->ho_va_ten_hoc_sinh,
                    'redirectUrl' => route('admin.admission.index'),
                ]);
            } else {
                $application = $service->createRegistration($data);
                $this->dispatch('show-success-modal', [
                    'name' => $application->ho_va_ten_hoc_sinh,
                    'redirectUrl' => route('admission.register'),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Lỗi lưu đơn: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('Admission::livewire.admission.registration-form');
    }
}
