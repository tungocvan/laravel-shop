<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdmissionApplication extends Model
{
    protected $table = 'admission_applications';

    protected $fillable = [
        'mhs',
        'status',
        // Bước 1: Thông tin học sinh
        'ho_va_ten_hoc_sinh',
        'gioi_tinh',
        'ngay_sinh',
        'dan_toc',
        'ma_dinh_danh',
        'quoc_tich',
        'ton_giao',
        'sdt_enetviet',
        'noi_sinh',
        'noi_sinh_px',
        'noi_sinh_tt',
        'noi_sinh_chi_tiet',
        'noi_dang_ky_khai_sinh_px',
        'noi_dang_ky_khai_sinh_tt',
        'que_quan',
        'que_quan_px',
        'que_quan_tt',
        // Bước 2: Địa chỉ
        'ttsn',
        'ttd',
        'ttkp',
        'ttpx',
        'ttttp',
        'dia_chi_thuong_tru',
        'htsn',
        'htd',
        'htkp',
        'htpx',
        'htttp',
        'noi_o_hien_tai',
        // Bước 3: Thông tin bổ sung
        'o_chung_voi',
        'quan_he_nguoi_nuoi_duong',
        'con_thu',
        'ts_anh_chi_em',
        'hoan_thanh_lop_la',
        'truong_mam_non',
        'kha_nang_hoc_sinh',
        'suc_khoe_can_luu_y',
        // Bước 4: Phụ huynh
        'ho_ten_cha',
        'nam_sinh_cha',
        'tdvh_cha',
        'tdcm_cha',
        'nghe_nghiep_cha',
        'chuc_vu_cha',
        'dien_thoai_cha',
        'cccd_cha',
        'ho_ten_me',
        'nam_sinh_me',
        'tdvh_me',
        'tdcm_me',
        'nghe_nghiep_me',
        'chuc_vu_me',
        'dien_thoai_me',
        'cccd_me',
        'ho_ten_nguoi_giam_ho',
        'quan_he_giam_ho',
        'dien_thoai_giam_ho',
        'cccd_giam_ho',
        // Bước 5: Đăng ký & Cam kết
        'anh_chi_ruot_trong_truong',
        'thanh_phan_gia_dinh',
        'loai_lop_dang_ky',
        'ck_goc_hoc_tap',
        'ck_sach_vo',
        'ck_hop_ph',
        'ck_tham_gia_hd',
        'ck_gan_gui',
        'ngay_lam_don',
        'nguoi_lam_don',
        // Bước 6: sắp xếp lớp
        'lop',
        'gvcn',
        'bao_mau',
        'pdf_path',
        'word_path'
    ];

    protected $casts = [
        'ngay_sinh' => 'date:Y-m-d',
        'skills' => 'array',
        'ck_goc_hoc_tap' => 'boolean',
        'kha_nang_hoc_sinh' => 'array',
        'suc_khoe_can_luu_y' => 'array',
    ];

    protected static function booted()
    {
        /**
         * 🔥 1. AUTO RESET STATUS → pending khi có chỉnh sửa
         */
        static::updating(function ($model) {

            $originalStatus = $model->getOriginal('status');

            // chỉ reset khi:
            // - trước đó đã có trạng thái (approved/rejected)
            // - và có thay đổi data (trừ status)
            if (
                in_array($originalStatus, ['approved', 'rejected']) &&
                $model->isDirty() &&
                !$model->isDirty('status')
            ) {
                $model->status = 'pending';

                \Log::info('STATUS AUTO RESET → pending', [
                    'id' => $model->id,
                    'from' => $originalStatus
                ]);
            }
        });

        /**
         * 🔥 2. DISPATCH JOB khi approved
         */
        static::updated(function ($model) {

            if ($model->wasChanged('status') && $model->status === 'approved') {

                // ❗ tránh duplicate job
                if (!empty($model->pdf_path)) {
                    \Log::info('SKIP DISPATCH: PDF đã tồn tại', [
                        'id' => $model->id
                    ]);
                    return;
                }

                \Log::info('DISPATCH GENERATE PDF JOB', [
                    'id' => $model->id
                ]);

                // 🔥 QUAN TRỌNG: sau khi DB commit xong mới chạy job
                \Modules\Admission\Jobs\GenerateAdmissionPdfJob::dispatch($model->id)
                    ->afterCommit();
            }
        });

        /**
         * 🔥 3. DELETE FILE khi xóa record
         */
        static::deleting(function ($model) {

            foreach (['pdf_path', 'word_path'] as $field) {

                $path = $model->$field;

                if (!$path) continue;

                $fullPath = storage_path('app/' . $path);

                if (!file_exists($fullPath)) {
                    \Log::warning('FILE NOT FOUND WHEN DELETING', [
                        'path' => $fullPath
                    ]);
                    continue;
                }

                try {
                    unlink($fullPath);

                    \Log::info('FILE DELETED', [
                        'path' => $fullPath
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('DELETE FILE ERROR', [
                        'path' => $fullPath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }
}
