<?php
namespace Modules\Admission\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Admission\Models\AdmissionLocation;

class AdmissionLocationSeeder extends Seeder
{
// Chạy lệnh: php artisan db:seed --class="Modules\Admission\database\seeders\AdmissionLocationSeeder"    
public function run()
    {
        $filePath = base_path('storage/app/import/admission/dvhc.csv'); // Đường dẫn tới file CSV của bạn
        if (! file_exists($filePath)) {
            $this->command?->warn("Bỏ qua Admission locations vì thiếu file: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Bỏ qua dòng đầu (header)

        $data = [];
        $batchSize = 500; // Nạp mỗi lần 500 dòng để tối ưu bộ nhớ

        while (($row = fgetcsv($file)) !== false) {
            if (! isset($row[1], $row[2], $row[5], $row[6]) || trim((string) $row[5]) === '') {
                continue;
            }

            // Mapping theo cấu trúc file: 
            // Cột 2 (index 1): Mã tỉnh, Cột 3 (index 2): Tên tỉnh, 
            // Cột 6 (index 5): Mã phường, Cột 7 (index 6): Tên phường
            $data[] = [
                'province_code' => $row[1],
                'province_name' => $row[2],
                'ward_code'     => $row[5],
                'ward_name'     => $row[6],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if (count($data) >= $batchSize) {
                $this->upsert($data);
                $data = [];
            }
        }

        if (count($data) > 0) {
            $this->upsert($data);
        }

        fclose($file);
    }

    private function upsert(array $data): void
    {
        AdmissionLocation::query()->upsert(
            $data,
            ['ward_code'],
            ['province_code', 'province_name', 'ward_name', 'updated_at']
        );
    }
}
