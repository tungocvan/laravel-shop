<?php

namespace Modules\Administrative\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Administrative\Models\AdministrativeProcedure;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'HC-001' => 'Điều chỉnh thông tin học sinh',
            'HC-002' => 'Cấp lại bản sao chứng nhận hoàn thành chương trình tiểu học',
            'HC-003' => 'Chuyển đến hoặc đi từ trường trong nước',
            'HC-004' => 'Chuyển đến từ trường ngoài nước',
            'HC-005' => 'Chuyển lớp',
            'HC-006' => 'Đơn miễn giảm học phí',
            'HC-007' => 'Xác nhận học sinh đang học tại trường',
            'HC-008' => 'Đơn đề nghị cấp bảng điểm',
        ];

        foreach ($names as $index => $name) {
            $procedure = AdministrativeProcedure::withTrashed()->updateOrCreate(
                ['code' => $index],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                    'max_file_size_kb' => 10240,
                    'max_files' => 5,
                    'is_active' => true,
                    'sort_order' => (int) Str::after($index, 'HC-'),
                ]
            );

            if ($procedure->trashed()) {
                $procedure->restore();
            }
        }
    }
}
