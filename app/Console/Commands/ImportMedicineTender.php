<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Medicine;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Carbon\Carbon;

class ImportMedicineTender extends Command
{
    protected $signature = 'import:medicine {file}';
    protected $description = 'Import dữ liệu thuốc dùng FastExcel qua Index (0,1,2...)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File không tồn tại: {$filePath}");
            return;
        }

        if ($this->confirm('Xác nhận xóa dữ liệu cũ để import mới?')) {
            $this->info("Đang làm sạch dữ liệu...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Tender::truncate();
            Medicine::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info("🚀 Đang đọc file và import dữ liệu (công nghệ Stream)...");

        $isFirstRow = true;
        $count = 0;

        // Khởi tạo FastExcel
        $fastExcel = new FastExcel();

        // XỬ LÝ LỖI FILE CSV BỊ GỘP CỘT (CHỈ ĐƯỢC 3 CỘT)
        // Nếu là file CSV, cấu hình dấu phân cách là ';' thay vì ','
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv') {
            $fastExcel->configureCsv(';');
        }

        try {
            // Hàm withoutHeaders() ép FastExcel trả về mảng số [0 => 'A', 1 => 'B', 2 => 'C'...]
            $fastExcel->withoutHeaders()->import($filePath, function ($row) use (&$isFirstRow, &$count) {
                // 1. Bỏ qua dòng số 1 (Dòng tiêu đề Header)
                if ($isFirstRow) {
                    $isFirstRow = false;
                    return;
                }

                // 2. Bỏ qua các dòng trống (Dựa vào cột C - index 2: Tên hoạt chất)
                if (empty($row[2])) {
                    return;
                }

                DB::transaction(function () use ($row) {
                    // Cột A = 0, B = 1, C = 2, D = 3, E = 4...
                    $medicine = Medicine::updateOrCreate(
                        [
                            'ten_hoat_chat' => (string)$row[2], // Cột C
                            'ten_biet_duoc' => (string)$row[4], // Cột E
                        ],
                        [
                            'stt_thong_tu'       => $row[1] ?? null, // A
                            'phan_nhom_thong_tu' => $row[2] ?? null, // B
                            'nong_do_ham_luong'  => $row[4] ?? null, // D
                            'dang_bao_che'       => $row[6] ?? null, // F
                            'duong_dung'         => $row[7] ?? null, // G
                            'don_vi_tinh'        => $row[8] ?? null, // H
                            'quy_cach_dong_goi'  => $row[9] ?? null, // I
                            'giay_phep_luu_hanh' => $row[10] ?? null, // J
                            'han_dung'           =>  $this->parseDate($row[11] ?? null),
                            'co_so_san_xuat'     => $row[12] ?? null, // L
                            'nuoc_san_xuat'      => $row[13] ?? null, // M

                            // Dùng hàm parseNumber để tránh lỗi chữ có chứa dấu phẩy
                            'gia_ke_khai'        => $this->parseNumber($row[14] ?? 0), // N
                            'nha_phan_phoi'      => $row[15] ?? null, // O
                            'don_gia'            => $this->parseNumber($row[16] ?? 0), // P
                            'gia_von'            => $this->parseNumber($row[17] ?? 0), // Q
                        ]
                    );

                    Tender::create([
                        'medicine_id'           => $medicine->id,
                        'trang_thai_trung_thau' => $row[18] ?? null, // R
                        'gia_trung_thau'        => $this->parseNumber($row[23] ?? 0), // S
                        'soluong_trung_thau'    => $this->parseNumber($row[24] ?? 0), // T
                        'noi_trung_thau'        => $row[25] ?? null, // U
                        'congty_trung_thau'     => $row[26] ?? null, // V
                        'so_qdtt'               => $row[27] ?? null, // W
                        'tg_trung_thau'         => $this->parseDate($row[28] ?? null), // X
                        'nhom_thuoc_tt'         => $row[29] ?? null, // Y
                    ]);
                });

                $count++;
            });

            $this->newLine();
            $this->info("✅ Import thành công tuyệt đối! Đã nạp {$count} dòng dữ liệu.");

        } catch (\Exception $e) {
            $this->error("Lỗi trong quá trình import: " . $e->getMessage());
        }
    }

    // --- CÁC HÀM BỔ TRỢ XỬ LÝ LỖI DATA --- //

    private function parseDate($date)
    {
        if (empty($date)) return null;
        if ($date instanceof \DateTime) return $date->format('Y-m-d');
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseNumber($value)
    {
        if (empty($value)) return 0;
        // Xóa dấu phẩy nghìn (VD: "1,000,000.50" -> "1000000.50")
        $cleanValue = str_replace([',', ' '], '', (string)$value);
        return is_numeric($cleanValue) ? (float)$cleanValue : 0;
    }
}
