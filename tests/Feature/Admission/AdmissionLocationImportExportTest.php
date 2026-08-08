<?php

namespace Tests\Feature\Admission;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Models\AdmissionLocation;
use Modules\Admission\Services\ImportExport;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class AdmissionLocationImportExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for Admission import/export tests.');
        }

        Schema::dropIfExists('admission_locations');
        (require base_path('Modules/Admission/database/migrations/2026_04_21_200716_create_locations_table.php'))->up();
    }

    public function test_exported_excel_can_be_imported_again(): void
    {
        AdmissionLocation::query()->create($this->locationData());

        $service = app(ImportExport::class);
        $path = $service->export();

        try {
            $report = app(ImportExport::class)->import(
                storage_path('app/public/'.$path),
                ['dry_run' => true]
            );
        } finally {
            Storage::disk('public')->delete($path);
        }

        $this->assertTrue($report['success']);
        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(1, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
    }

    public function test_replace_rolls_back_when_any_row_is_invalid(): void
    {
        AdmissionLocation::query()->create($this->locationData());

        $path = sys_get_temp_dir().'/admission-location-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'province_code' => '79',
            'province_name' => 'Thành phố Hồ Chí Minh',
            'ward_code' => null,
            'ward_name' => 'Phường Bến Thành',
        ]])))->export($path);

        try {
            $report = app(ImportExport::class)->import($path, ['mode' => 'replace']);
        } finally {
            @unlink($path);
        }

        $this->assertFalse($report['success']);
        $this->assertTrue($report['debug']['rolled_back']);
        $this->assertSame(0, $report['success_rows']);
        $this->assertDatabaseHas('admission_locations', ['ward_code' => '00001']);
    }

    private function locationData(): array
    {
        return [
            'province_code' => '01',
            'province_name' => 'Thành phố Hà Nội',
            'ward_code' => '00001',
            'ward_name' => 'Phường Hoàn Kiếm',
        ];
    }
}
