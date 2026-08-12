<?php

namespace Modules\Admission\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\AdmissionCatalog;
use Modules\Admission\Models\AdmissionLocation;

class AdmissionRegistrationService
{
    public function __construct(
        private readonly AdmissionService $admissionService,
        private readonly SchoolSettingService $schoolSettingService,
    ) {
    }

    public function options(): array
    {
        return [
            'provinces' => AdmissionLocation::query()
                ->select('province_name')
                ->distinct()
                ->orderBy('province_name')
                ->get()
                ->toArray(),
            'ethnicities' => AdmissionCatalog::query()
                ->where('type', 'ethnicity')
                ->orderBy('value')
                ->get()
                ->toArray(),
            'religions' => AdmissionCatalog::query()
                ->where('type', 'religion')
                ->orderBy('value')
                ->get()
                ->toArray(),
            'registrationClasses' => $this->schoolSettingService->registrationClasses(),
        ];
    }

    public function wardsForProvince(?string $province): array
    {
        $province = trim((string) $province);

        if ($province === '') {
            return [];
        }

        return AdmissionLocation::query()
            ->where('province_name', $province)
            ->orderBy('ward_name')
            ->get()
            ->toArray();
    }

    public function findForEdit(int $id): AdmissionApplication
    {
        return AdmissionApplication::query()->findOrFail($id);
    }

    public function toForm(AdmissionApplication $application): array
    {
        return [
            'HoVaTenHocSinh' => $application->ho_va_ten_hoc_sinh ?? '',
            'GioiTinh' => $application->gioi_tinh ?? '',
            'NgaySinh' => $application->ngay_sinh ? Carbon::parse($application->ngay_sinh)->format('Y-m-d') : '',
            'DanToc' => $application->dan_toc ?? 'Kinh',
            'MaDinhDanh' => $application->ma_dinh_danh ?? '',
            'QuocTich' => $application->quoc_tich ?? 'Việt Nam',
            'TonGiao' => $application->ton_giao ?? 'Không',
            'SDTEnetViet' => $application->sdt_enetviet ?? '',
            'NoiSinh' => $application->noi_sinh ?? '',
            'NoiSinhPx' => $application->noi_sinh_px ?? '',
            'NoiSinhTt' => $application->noi_sinh_tt ?? '',
            'NoiSinhChiTiet' => $application->noi_sinh_chi_tiet ?? '',
            'NoiDangKyKhaiSinhPx' => $application->noi_dang_ky_khai_sinh_px ?? '',
            'NoiDangKyKhaiSinhTt' => $application->noi_dang_ky_khai_sinh_tt ?? '',
            'QueQuan' => $application->que_quan ?? '',
            'QueQuanPx' => $application->que_quan_px ?? '',
            'QueQuanTt' => $application->que_quan_tt ?? '',
            'TTSN' => $application->ttsn ?? '',
            'TTD' => $application->ttd ?? '',
            'TTKP' => $application->ttkp ?? '',
            'TTPX' => $application->ttpx ?? '',
            'TTTTP' => $application->ttttp ?? '',
            'HTSN' => $application->htsn ?? '',
            'HTD' => $application->htd ?? '',
            'HTKP' => $application->htkp ?? '',
            'HTPX' => $application->htpx ?? '',
            'HTTTP' => $application->htttp ?? '',
            'OChungVoi' => $application->o_chung_voi ?? '',
            'QuanHeNguoiNuoiDuong' => $application->quan_he_nguoi_nuoi_duong ?? '',
            'ConThu' => $application->con_thu ?? '',
            'TSAnhChiEm' => $application->ts_anh_chi_em ?? '',
            'HoanThanhLopLa' => $application->hoan_thanh_lop_la ?? 'Có',
            'TruongMamNon' => $application->truong_mam_non ?? '',
            'KhaNangHocSinh' => is_array($application->kha_nang_hoc_sinh) ? $application->kha_nang_hoc_sinh : [],
            'SucKhoeCanLuuY' => is_array($application->suc_khoe_can_luu_y) ? $application->suc_khoe_can_luu_y : [],
            'SucKhoeKhac' => '',
            'HoTenCha' => $application->ho_ten_cha ?? '',
            'NamSinhCha' => $application->nam_sinh_cha ?? '',
            'TdvhCha' => $application->tdvh_cha ?? '',
            'TdcmCha' => $application->tdcm_cha ?? '',
            'NgheNghiepCha' => $application->nghe_nghiep_cha ?? 'LĐTD',
            'ChucVuCha' => $application->chuc_vu_cha ?? '',
            'DienThoaiCha' => $application->dien_thoai_cha ?? '',
            'CCCDCha' => $application->cccd_cha ?? '',
            'HoTenMe' => $application->ho_ten_me ?? '',
            'NamSinhMe' => $application->nam_sinh_me ?? '',
            'TdvhMe' => $application->tdvh_me ?? '',
            'TdcmMe' => $application->tdcm_me ?? '',
            'NgheNghiepMe' => $application->nghe_nghiep_me ?? 'LĐTD',
            'ChucVuMe' => $application->chuc_vu_me ?? '',
            'DienThoaiMe' => $application->dien_thoai_me ?? '',
            'CCCDMe' => $application->cccd_me ?? '',
            'HoTenNguoiGiamHo' => $application->ho_ten_nguoi_giam_ho ?? '',
            'QuanHeGiamHo' => $application->quan_he_giam_ho ?? '',
            'DienThoaiGiamHo' => $application->dien_thoai_giam_ho ?? '',
            'CCCDGiamHo' => $application->cccd_giam_ho ?? '',
            'LoaiLopDangKy' => $application->loai_lop_dang_ky ?? '',
            'CK_GocHocTap' => (bool) $application->ck_goc_hoc_tap,
            'CK_SachVo' => (bool) $application->ck_sach_vo,
            'CK_HopPH' => (bool) $application->ck_hop_ph,
            'CK_ThamGiaHD' => (bool) $application->ck_tham_gia_hd,
            'CK_GanGui' => (bool) $application->ck_gan_gui,
            'Lop' => $application->lop ?? '',
            'Gvcn' => $application->gvcn ?? '',
            'BaoMau' => $application->bao_mau ?? '',
            'NgayLamDon' => $application->ngay_lam_don ?? '',
            'NguoiLamDon' => $application->nguoi_lam_don ?? '',
        ];
    }

    public function create(array $form): AdmissionApplication
    {
        $form['Status'] = 'pending';

        return DB::transaction(function () use ($form) {
            $application = $this->admissionService->createRegistration($form);
            $this->syncBirthplaceDetail($application, $form);

            return $application->fresh();
        });
    }

    public function update(int $id, array $form): AdmissionApplication
    {
        unset($form['Status']);

        return DB::transaction(function () use ($id, $form) {
            $application = $this->admissionService->updateRegistration($id, $form);
            $this->syncBirthplaceDetail($application, $form);

            return $application->fresh();
        });
    }

    private function syncBirthplaceDetail(AdmissionApplication $application, array $form): void
    {
        $detail = $form['NoiSinhChiTiet'] ?? '';

        if ($application->noi_sinh_chi_tiet !== $detail) {
            $application->forceFill(['noi_sinh_chi_tiet' => $detail])->saveQuietly();
        }
    }
}
