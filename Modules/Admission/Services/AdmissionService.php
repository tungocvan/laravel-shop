<?php

namespace Modules\Admission\Services;

use Modules\Admission\Models\AdmissionApplication;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\Storage;

class AdmissionService
{
    /**
     * TẠO MỚI HỒ SƠ
     */
    // public function createRegistration(array $formData)
    // {
    //     // Tạo mã hồ sơ tự động
    //     $mhs = 'NTD' . date('Y') . str_pad(AdmissionApplication::count() + 1, 4, '0', STR_PAD_LEFT);

    //     // Chuẩn hóa dữ liệu trước khi lưu
    //     $data = $this->prepareData($formData);
    //     $data['mhs'] = $mhs;
    //     $data['status'] = $formData['Status'] ?? '';

    //     return AdmissionApplication::create($data);
    // }

    public function createRegistration(array $formData): AdmissionApplication
    {
        return DB::transaction(function () use ($formData) {

            $data = $this->prepareData($formData);
            $data['status'] = $formData['Status'] ?? '';

            $nextId = (AdmissionApplication::lockForUpdate()->max('id') ?? 0) + 1;

            $data['mhs'] = sprintf(
                'NVH%s%04d',
                now()->year,
                $nextId
            );

            return AdmissionApplication::create($data);
        });
    }

    /**
     * CẬP NHẬT HỒ SƠ (Dành cho Admin)
     */
    public function updateRegistration($id, array $formData)
    {
        $application = AdmissionApplication::findOrFail($id);

        // Chuẩn hóa dữ liệu
        $data = $this->prepareData($formData);

        // Admin có quyền cập nhật cả trạng thái
        if (isset($formData['Status'])) {
            $data['status'] = $formData['Status'];
        }

        $application->update($data);
        return $application;
    }

    /**
     * HÀM CHUẨN HÓA DỮ LIỆU TRUNG TÂM (Tránh lỗi SQL 1366 và lỗi định dạng)
     */
    private function prepareData(array $formData)
    {
        //$formData['KhaNangHocSinoh'] = (array) ($f

        $date = str_replace('/', '-', trim($formData['NgaySinh']));
        $data = [
            // 1. Thông tin học sinh
            'ho_va_ten_hoc_sinh' => $formData['HoVaTenHocSinh'] ?? null,
            'gioi_tinh'          => $formData['GioiTinh'] ?? null,
            'ngay_sinh' => !empty($date) ? $this->normalizeDate($date) : null,
            'dan_toc'            => $formData['DanToc'] ?? null,
            'ma_dinh_danh'       => $formData['MaDinhDanh'] ?? null,
            'quoc_tich'          => $formData['QuocTich'] ?? null,
            'ton_giao'           => $formData['TonGiao'] ?? null,
            'sdt_enetviet'       => $formData['SDTEnetViet'] ?? null,
            'noi_sinh_px'        => $formData['NoiSinhPx'] ?? null,
            'noi_sinh_tt'        => $formData['NoiSinhTt'] ?? null,
            'noi_sinh'           => $formData['NoiSinh'] ?? null,
            'noi_sinh_chi_tiet'  => $formData['NoiSinh'] ?? '',
            'noi_dang_ky_khai_sinh_px' => $formData['NoiDangKyKhaiSinhPx'] ?? null,
            'noi_dang_ky_khai_sinh_tt' => $formData['NoiDangKyKhaiSinhTt'] ?? null,
            'que_quan_px'        => $formData['QueQuanPx'] ?? null,
            'que_quan_tt'        => $formData['QueQuanTt'] ?? null,
            'que_quan'           => $formData['QueQuanPx'] . ", " . $formData['QueQuanTt'] ?? '',

            // 2. Địa chỉ
            'ttsn'               => $formData['TTSN'] ?? '',
            'ttd'                => $formData['TTD'] ?? '',
            'ttkp'               => $formData['TTKP'] ?? '',
            'ttpx'               => $formData['TTPX'] ?? '',
            'ttttp'              => $formData['TTTTP'] ?? '',
            'htsn'               => $formData['HTSN'] ?? '',
            'htd'                => $formData['HTD'] ?? '',
            'htkp'               => $formData['HTKP'] ?? '',
            'htpx'               => $formData['HTPX'] ?? '',
            'htttp'              => $formData['HTTTP'] ?? '',
            'noi_o_hien_tai'     => $formData['NoiOHienTai'] ?? '',

            // 3. Thông tin bổ sung (Ép kiểu Integer để tránh lỗi 1366)
            'o_chung_voi'        => $formData['OChungVoi'] ?? null,
            'quan_he_nguoi_nuoi_duong' => $formData['QuanHeNguoiNuoiDuong'] ?? null,
            'con_thu'            => (isset($formData['ConThu']) && $formData['ConThu'] !== '') ? (int)$formData['ConThu'] : null,
            'ts_anh_chi_em'      => (isset($formData['TSAnhChiEm']) && $formData['TSAnhChiEm'] !== '') ? (int)$formData['TSAnhChiEm'] : null,
            'hoan_thanh_lop_la'  => $formData['HoanThanhLopLa'] ?? null,
            'truong_mam_non'     => $formData['TruongMamNon'] ?? null,
            'kha_nang_hoc_sinh' =>  $formData['KhaNangHocSinh'] ?? [],
            'suc_khoe_can_luu_y' => $formData['SucKhoeCanLuuY'] ?? [],
            // 4. Cha - Mẹ - Giám hộ
            'ho_ten_cha'         => $formData['HoTenCha'] ?? null,
            'nam_sinh_cha'       => (isset($formData['NamSinhCha']) && $formData['NamSinhCha'] !== '') ? (int)$formData['NamSinhCha'] : null,
            'tdvh_cha'           => $formData['TdvhCha'] ?? null,
            'tdcm_cha'           => $formData['TdcmCha'] ?? null,
            'nghe_nghiep_cha'    => $formData['NgheNghiepCha'] ?? null,
            'chuc_vu_cha'        => $formData['ChucVuCha'] ?? null,
            'dien_thoai_cha'     => $formData['DienThoaiCha'] ?? null,
            'cccd_cha'           => $formData['CCCDCha'] ?? null,

            'ho_ten_me'          => $formData['HoTenMe'] ?? null,
            'nam_sinh_me'        => (isset($formData['NamSinhMe']) && $formData['NamSinhMe'] !== '') ? (int)$formData['NamSinhMe'] : null,
            'tdvh_me'            => $formData['TdvhMe'] ?? null,
            'tdcm_me'            => $formData['TdcmMe'] ?? null,
            'nghe_nghiep_me'     => $formData['NgheNghiepMe'] ?? null,
            'chuc_vu_me'         => $formData['ChucVuMe'] ?? null,
            'dien_thoai_me'      => $formData['DienThoaiMe'] ?? null,
            'cccd_me'            => $formData['CCCDMe'] ?? null,

            'ho_ten_nguoi_giam_ho' => $formData['HoTenNguoiGiamHo'] === '' ? $formData['HoTenMe'] : $formData['HoTenNguoiGiamHo'],
            'quan_he_giam_ho'      => $formData['QuanHeGiamHo'] ?? null,
            'dien_thoai_giam_ho'   => $formData['DienThoaiGiamHo'] === '' ? $formData['DienThoaiMe'] : $formData['DienThoaiGiamHo'],
            'cccd_giam_ho'         => $formData['CCCDGiamHo'] === '' ? $formData['CCCDMe'] : $formData['CCCDGiamHo'],

            // 5. Đăng ký & Checkbox cam kết
            'anh_chi_ruot_trong_truong' => $formData['AnhChiRuotTrongTruong'] ?? null,
            'thanh_phan_gia_dinh'       => $formData['ThanhPhanGiaDinh'] ?? null,
            'loai_lop_dang_ky'          => $formData['LoaiLopDangKy'] ?? null,
            'ck_goc_hoc_tap'            => filter_var($formData['CK_GocHocTap'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'ck_sach_vo'                => filter_var($formData['CK_SachVo'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'ck_hop_ph'                 => filter_var($formData['CK_HopPH'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'ck_tham_gia_hd'            => filter_var($formData['CK_ThamGiaHD'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'ck_gan_gui'                => filter_var($formData['CK_GanGui'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'ngay_lam_don'              => !empty($formData['NgayLamDon']) ? Carbon::parse($formData['NgayLamDon'])->format('Y-m-d') : date('Y-m-d'),
            'nguoi_lam_don'             => $formData['NguoiLamDon'] === '' ? $formData['HoTenMe'] : $formData['NguoiLamDon'],
            'lop'          => $formData['Lop'] ?? '',
            'gvcn'          => $formData['Gvcn'] ?? '',
            'bao_mau'          => $formData['BaoMau'] ?? '',
        ];
        // dd($data);
        return $data;
    }
    // public function generateQrImage($url)
    // {
    //     $fileName = 'qr_' . Str::random(10) . '.png';
    //     $path = storage_path("app/public/{$fileName}");

    //     QrCode::format('png')
    //         ->size(300)
    //         ->errorCorrection('H')
    //         ->generate($url, $path);

    //     return $path;
    // }
    /**
     * DỮ LIỆU ĐỔ VÀO WORD (Sử dụng key của file Word mẫu)
     */
    public function getDataForTemplate($id)
    {
        $app = AdmissionApplication::findOrFail($id);

        $data = [
            'MaHoSo'            => $app->mhs,
            'HoVaTenHocSinh'    => Str::upper($app->ho_va_ten_hoc_sinh),
            'GioiTinh'          => $app->gioi_tinh,
            'NgaySinh'          => $app->ngay_sinh ? Carbon::parse($app->ngay_sinh)->format('d/m/Y') : '',
            'DanToc'            => $app->dan_toc,
            'MaDinhDanh'        => $app->ma_dinh_danh,
            'QuocTich'          => $app->quoc_tich,
            'TonGiao'           => $app->ton_giao,
            'SDTEnetViet'       => $app->sdt_enetviet,
            'NoiSinh'           => $app->noi_sinh,
            'NoiSinhPx'         => $app->noi_sinh_px,
            'NoiSinhTt'         => $app->noi_sinh_tt,
            'NoiSinhChiTiet'   => $app->noi_sinh_chi_tiet,
            'NoiDangKyKhaiSinhPx' => $app->noi_dang_ky_khai_sinh_px,
            'NoiDangKyKhaiSinhTt' => $app->noi_dang_ky_khai_sinh_tt,
            'QueQuan'           => $app->que_quan,
            'QueQuanPx'         => $app->que_quan_px,
            'QueQuanTt'         => $app->que_quan_tt,
            'TTSN'              => $app->ttsn ?? '',
            'TTD'               => $app->ttd ?? '',
            'TTKP'              => $app->ttkp ?? '',
            'TTPX'              => $app->ttpx ?? '',
            'TTTTP'             => $app->ttttp ?? '',
            'HTSN'              => $app->htsn ?? '',
            'HTD'               => $app->htd ?? '',
            'HTKP'              => $app->htkp ?? '',
            'HTPX'              => $app->htpx ?? '',
            'HTTTP'             => $app->htttp ?? '',
            'OChungVoi'         => $app->o_chung_voi ?? 'Cha mẹ',
            'ConThu'            => $app->con_thu ?? '……',
            'TSAnhChiEm'        => $app->ts_anh_chi_em ?? '……',
            'HoanThanhLopLa'    => $app->hoan_thanh_lop_la ?? '…………………………',
            'TruongMamNon'      => $app->truong_mam_non ?? '…………………………',
            'KhaNangHocSinh'    =>   !empty($app->kha_nang_hoc_sinh)
                ? implode(', ', $app->kha_nang_hoc_sinh)
                : '',
            'SucKhoeCanLuuY'    =>  !empty($app->suc_khoe_can_luu_y)
                ? implode(', ', $app->suc_khoe_can_luu_y)
                : '……………………………………………',
            'HoTenCha'          => $app->ho_ten_cha ?? '',
            'NamSinhCha'        => $app->nam_sinh_cha ?? '',
            'TdvhCha'           => $app->tdvh_cha ?? '',
            'TdcmCha'           => $app->tdcm_cha ?? '',
            'NgheNghiepCha'     => $app->nghe_nghiep_cha ?? '',
            'ChuvuCha'          => $app->chuc_vu_cha ?? '',
            'DienThoaiCha'      => $app->dien_thoai_cha ?? '',
            'CCCDCha'           => $app->cccd_cha ?? '',
            'HoTenMe'           => $app->ho_ten_me ?? '',
            'NamSinhMe'         => $app->nam_sinh_me ?? '',
            'TdvhMe'            => $app->tdvh_me ?? '',
            'TdcmMe'            => $app->tdcm_me ?? '',
            'NgheNghiepMe'      => $app->nghe_nghiep_me ?? '',
            'ChuvuMe'           => $app->chuc_vu_me ?? '',
            'DienThoaiMe'       => $app->dien_thoai_me ?? '',
            'CCCDMe'            => $app->cccd_me ?? '',
            'HoTenNguoiGiamHo'  => $app->ho_ten_nguoi_giam_ho ?? '',
            'DienThoaiGiamHo'   => $app->dien_thoai_giam_ho ?? '',
            'CCCDGiamHo'        => $app->cccd_giam_ho ?? '',
            'LoaiLopDangKy'     => $app->loai_lop_dang_ky ?? '',
            'Ngay'              => Carbon::parse($app->created_at)->format('d'),
            'Thang'             => Carbon::parse($app->created_at)->format('m'),
            'Nam'               => Carbon::parse($app->created_at)->format('Y'),
            'NguoiLamDon'       => $app->nguoi_lam_don  ?? '',
        ];
        $data['THUONG'] = $app->loai_lop_dang_ky === 'Lớp thường' ? '☑' : '☐';
        $data['TCTA'] = $app->loai_lop_dang_ky === 'Tăng cường Tiếng Anh' ? '☑' : '☐';
        $data['TH'] = $app->loai_lop_dang_ky === 'Tích hợp' ? '☑' : '☐';
        $data['TATOAN'] = $app->loai_lop_dang_ky === 'Tăng cường TA + Toán và Khoa học' ? '☑' : '☐';
        $data['kn1'] = $app->loai_lop_dang_ky === 'Tăng cường TA + Toán và Khoa học' ? '☑' : '☐';
        $school = app(SchoolSettingService::class)->all();
        $data['SchoolName'] = $school['school_name'];
        $data['SchoolNameUCase'] = mb_convert_case(mb_strtolower($school['school_name'], 'UTF-8'), MB_CASE_TITLE, "UTF-8");
        $data['SchoolYear'] = $school['school_year'];
        $data['Principal'] = $school['principal'];
        $data['SchoolManagingAgency'] = $school['school_managing_agency'];
        // dd($data);

        $options = [
            'KN1' => 'Mạnh dạn tự tin',
            'KN2' => 'Biết bơi',
            'KN3' => 'Đã biết đọc, biết viết',
            'KN4' => 'Biết đàn',
            'KN5' => 'Biết hát',
            'KN6' => 'Phát âm rõ ràng',
        ];
        if (is_string($app->kha_nang_hoc_sinh)) {
            $skills = array_map('trim', explode(',', $app->kha_nang_hoc_sinh));
        } elseif (is_array($app->kha_nang_hoc_sinh)) {
            $skills = $app->kha_nang_hoc_sinh;
        } else {
            $skills = [];
        }

        foreach ($options as $key => $label) {
            $data[$key] = in_array($label, $skills) ? '☑' : '☐';
        }


        return $data;
    }

    /**
     * Lấy danh sách cho Admin (CRUD)
     */
    public function getPaginatedList(array $filters = [], $perPage = 15)
    {
        $query = AdmissionApplication::query();
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('ho_va_ten_hoc_sinh', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('ma_dinh_danh', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('mhs', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->latest()->paginate($perPage);
    }

    public function deleteRegistration($id)
    {
        return AdmissionApplication::findOrFail($id)->delete();
    }

    public function generateBienNhan($app)
    {
        $templatePath = storage_path('app/templates/bien-nhan.docx');

        if (!file_exists($templatePath)) {
            abort(500, 'Không tìm thấy file template');
        }

        // ======================
        // TEMP FILE
        // ======================
        $fileName = 'bien-nhan-' . $app->id . '.docx';
        $tempDir = storage_path('app/temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempFile = $tempDir . '/' . $fileName;

        // ======================
        // QR URL
        // ======================
        $url = route('admission.search', array_filter([
            'ma_dinh_danh' => $app->ma_dinh_danh ?? null,
            'password' => $app->ngay_sinh
                ? Carbon::parse($app->ngay_sinh)->format('dmY')
                : null,
        ]));

        // ======================
        // GENERATE QR IMAGE
        // ======================
        $qrResult = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(300)
            ->margin(10)
            ->build();

        // lưu QR file tạm
        $qrFileName = 'qr_' . $app->id . '.png';
        $qrPath = $tempDir . '/' . $qrFileName;

        $qrResult->saveToFile($qrPath);

        // ======================
        // LOAD TEMPLATE WORD
        // ======================
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        // ======================
        // REPLACE TEXT
        // ======================
        $template->setValue('HoVaTenHocSinh', $app->ho_va_ten_hoc_sinh);
        $template->setValue(
            'NgaySinh',
            $app->ngay_sinh
                ? Carbon::parse($app->ngay_sinh)->format('d/m/Y')
                : ''
        );
        $template->setValue('MaHoSo', $app->mhs);
        $school = app(SchoolSettingService::class)->all();
        $template->setValue('SchoolName', $school['school_name']);
        $template->setValue('SchoolNameUCase', mb_convert_case(mb_strtolower($school['school_name'], 'UTF-8'), MB_CASE_TITLE, "UTF-8"));
        $template->setValue('SchoolYear', $school['school_year']);
        $template->setValue('SchoolManagingAgency', $school['school_managing_agency']);

        // ======================
        // INSERT QR IMAGE
        // ======================
        $template->setImageValue('QR_CODE', [
            'path' => $qrPath,
            'width' => 120,
            'height' => 120,
            'ratio' => false,
        ]);

        // ======================
        // SAVE WORD FILE
        // ======================
        $template->saveAs($tempFile);

        // ======================
        // RETURN DOWNLOAD
        // ======================
        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
    private function normalizeDate($date)
    {
        if (empty($date)) {
            return null;
        }

        $date = trim($date);
        $date = str_replace('/', '-', $date);

        foreach (['d-m-Y', 'Y-m-d', 'd-m-Y H:i:s', 'Y-m-d H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        return Carbon::parse($date)->format('Y-m-d');
    }
}
