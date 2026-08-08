<?php

namespace Modules\Admission\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Admission\Models\AdmissionCatalog;

class AdmissionCatalogSeeder extends Seeder
{
    // Chạy lệnh: php artisan db:seed --class="Modules\Admission\database\seeders\AdmissionCatalogSeeder"   
    public function run()
    {
        // 1. Nạp Dân tộc
        $this->seedFromCsv(base_path('storage/app/import/admission/dan_toc.csv'), 'ethnicity', 1); // Cột tên ở index 1

        // 2. Nạp Tôn giáo
        $this->seedFromCsv(base_path('storage/app/import/admission/ton_giao.csv'), 'religion', 2); // Cột tên ở index 2
    }

    private function seedFromCsv($path, $type, $valueIndex)
    {
        if (! file_exists($path)) {
            $this->command?->warn("Bỏ qua Admission catalog [{$type}] vì thiếu file: {$path}");
            return;
        }

        $file = fopen($path, 'r');
        fgetcsv($file); // Bỏ qua dòng header

        while (($row = fgetcsv($file)) !== false) {
            // 1. Kiểm tra dòng có dữ liệu không và cột giá trị có bị trống không
            if (!isset($row[$valueIndex]) || empty(trim($row[$valueIndex]))) {
                continue;
            }

            // 2. Xử lý sort_order: Ép kiểu về int, nếu trống hoặc lỗi thì để 0
            $sortOrder = isset($row[0]) && is_numeric($row[0]) ? (int)$row[0] : 0;

            AdmissionCatalog::query()->updateOrCreate(
                ['type' => $type, 'value' => trim($row[$valueIndex])],
                ['sort_order' => $sortOrder, 'is_active' => true]
            );
        }
        fclose($file);
    }
}
