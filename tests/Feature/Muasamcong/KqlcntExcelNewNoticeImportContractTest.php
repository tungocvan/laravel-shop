<?php

namespace Tests\Feature\Muasamcong;

use Tests\TestCase;

class KqlcntExcelNewNoticeImportContractTest extends TestCase
{
    public function test_new_notice_preview_and_confirm_use_same_existing_import_service(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertNotFalse($source);
        foreach ([
            '$createNew = $isV2',
            "->where('notify_no', \$scope['notify_no'])->exists()",
            "'create_new' => \$createNew",
            "'status' => 'created'",
            "'created' => collect(\$rows)->where('status', 'created')->count()",
            'return $this->confirmNewNotice($batch);',
            "'source' => 'excel'",
            "'synced_from_catalog_at' => now()",
            "KqlcntAwardItem::query()->create(\$data)",
            "'created' => \$created",
        ] as $contract) {
            if ($contract === "'status' => 'created'") {
                $this->assertStringContainsString("'created' : (", $source);
                continue;
            }
            $this->assertStringContainsString($contract, $source);
        }
    }

    public function test_existing_notice_stays_update_only_and_new_import_is_all_or_nothing(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertStringContainsString("elseif (! \$createNew)", $source);
        $this->assertStringContainsString('Tạo hồ sơ mới yêu cầu tất cả dòng thuốc hợp lệ; không import một phần.', $source);
        $this->assertStringContainsString('TBMT đã tồn tại hoặc đã được import bởi phiên khác.', $source);
        $this->assertStringContainsString('DB::transaction(function () use ($batch, $overwrite)', $source);
        $this->assertStringContainsString("'created' => 0", $source);
    }

    public function test_preview_labels_creation_and_keeps_manual_allocation_unconfigured(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-award-import-preview.blade.php'));
        $this->assertStringContainsString('Tạo hồ sơ trúng thầu mới (Sheet 1)', $blade);
        $this->assertStringContainsString('Xác nhận tạo mới', $blade);
        $this->assertStringContainsString('Không sao chép phân bổ hoặc chính sách kinh doanh.', $blade);
        $service = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertStringNotContainsString('DrugBidAwardDistribution::query()->create(', $service);
    }
}
