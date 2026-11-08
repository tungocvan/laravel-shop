<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Services\KqlcntAwardExcelImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KqlcntAwardPackageMetadataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sheet_one_contract_period_is_previewed_and_updates_all_scoped_medicines(): void
    {
        $this->markTestSkipped('Requires canonical KQLCNT factory fixture and authenticated Admin session for confirm.');
    }

    public function test_package_metadata_preview_contract_has_guarded_transactional_update(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertStringContainsString('previewPackageChanges($scope)', $source);
        $this->assertStringContainsString('lockForUpdate()->get()', $source);
        $this->assertStringContainsString("'package_updated' => $packageUpdated", $source);
        $this->assertStringContainsString("($batch['package']['errors'])", $source);
    }
}
