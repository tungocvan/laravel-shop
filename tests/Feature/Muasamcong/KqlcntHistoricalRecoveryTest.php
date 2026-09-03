<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntImportBatch;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Modules\Muasamcong\Services\KqlcntHistoricalImportService;
use Tests\TestCase;

class KqlcntHistoricalRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_rejects_notify_number_outside_contractor_search_scope(): void
    {
        $search = $this->storedSearch();
        $batch = $this->batch($search->id, 'IB-OUTSIDE');

        $preview = app(KqlcntHistoricalImportService::class)->preview($batch, $this->mapping());

        $this->assertSame(1, $preview->error_rows);
        $this->assertSame('error', $preview->preview_rows[0]['status']);
        $this->assertStringContainsString('không thuộc lịch sử nhà thầu', implode(' ', $preview->preview_rows[0]['errors']));
    }

    public function test_confirm_persists_imported_award_without_calling_external_api(): void
    {
        Http::fake();
        $search = $this->storedSearch();
        $batch = $this->batch($search->id, 'IB001');
        $service = app(KqlcntHistoricalImportService::class);

        $preview = $service->preview($batch, $this->mapping());
        $this->assertSame(1, $preview->valid_rows);
        $this->assertSame(0, $preview->error_rows);

        $service->confirm($preview);

        Http::assertNothingSent();
        $this->assertDatabaseHas('muasamcong_kqlcnt_award_items', [
            'notify_no' => 'IB001',
            'contractor_code' => 'vn0315681994',
            'medicine_name' => 'Paracetamol 500mg',
            'source' => 'import',
        ]);
        $this->assertDatabaseHas('muasamcong_kqlcnt_records', [
            'notify_no' => 'IB001',
            'contractor_code' => 'vn0315681994',
            'data_source' => 'import',
        ]);
    }

    public function test_duplicate_and_conflict_are_previewed_before_confirmation(): void
    {
        $search = $this->storedSearch();
        $service = app(KqlcntHistoricalImportService::class);
        $first = $service->preview($this->batch($search->id, 'IB001'), $this->mapping());
        $service->confirm($first);

        $duplicate = $service->preview($this->batch($search->id, 'IB001'), $this->mapping());
        $this->assertSame(1, $duplicate->duplicate_rows);

        $conflict = $service->preview($this->batch($search->id, 'IB001', 450), $this->mapping());
        $this->assertSame(1, $conflict->conflict_rows);

        $service->confirm($conflict, false);
        $this->assertSame('385.0000', KqlcntAwardItem::query()->firstOrFail()->winning_price);

        $overwrite = $service->preview($this->batch($search->id, 'IB001', 450), $this->mapping());
        $service->confirm($overwrite, true);
        $this->assertSame('450.0000', KqlcntAwardItem::query()->firstOrFail()->winning_price);
    }

    public function test_import_marks_existing_api_snapshot_as_mixed_without_removing_api_snapshot(): void
    {
        $search = $this->storedSearch();
        KqlcntRecord::create([
            'notify_no' => 'IB001',
            'contractor_code' => 'vn0315681994',
            'published' => true,
            'current_contractor_won' => true,
            'contracts' => [['contractNo' => 'HD-API-01']],
            'verified_lots' => [['lotNo' => '1']],
            'data_source' => 'api',
            'synced_at' => now(),
        ]);

        $service = app(KqlcntHistoricalImportService::class);
        $batch = $service->preview($this->batch($search->id, 'IB001'), $this->mapping());
        $service->confirm($batch);

        $record = KqlcntRecord::query()->firstOrFail();
        $this->assertSame('mixed', $record->data_source);
        $this->assertSame('HD-API-01', $record->contracts[0]['contractNo']);
        $this->assertSame('1', $record->verified_lots[0]['lotNo']);
    }

    private function storedSearch()
    {
        return app(ContractorSearchArchiveService::class)->store(
            'vn0315681994',
            'CÔNG TY DƯỢC MẪU',
            '2021-01-01',
            null,
            [
                'reported_total' => 2,
                'total_pages' => 1,
                'items' => [
                    ['id' => '1', 'notifyNo' => 'IB001', 'bidName' => 'Gói thuốc 1', 'createdDate' => '2026-08-01T00:00:00'],
                    ['id' => '2', 'notifyNo' => 'IB002', 'bidName' => 'Gói thuốc 2', 'createdDate' => '2026-07-01T00:00:00'],
                ],
            ]
        );
    }

    private function batch(int $searchId, string $notifyNo, float $winningPrice = 385): KqlcntImportBatch
    {
        $headers = ['Mã TBMT', 'Mã lô', 'Tên thuốc', 'Hoạt chất', 'Số lượng', 'Giá trúng thầu'];

        return KqlcntImportBatch::create([
            'contractor_search_id' => $searchId,
            'original_name' => 'recovery.xlsx',
            'checksum' => str_repeat('a', 64),
            'status' => 'staged',
            'headers' => $headers,
            'raw_rows' => [[
                'Mã TBMT' => $notifyNo,
                'Mã lô' => '1',
                'Tên thuốc' => 'Paracetamol 500mg',
                'Hoạt chất' => 'Paracetamol',
                'Số lượng' => 120000,
                'Giá trúng thầu' => $winningPrice,
            ]],
            'mapping' => $this->mapping(),
            'total_rows' => 1,
        ]);
    }

    private function mapping(): array
    {
        return [
            'notify_no' => 'Mã TBMT',
            'lot_no' => 'Mã lô',
            'medicine_name' => 'Tên thuốc',
            'active_ingredient' => 'Hoạt chất',
            'quantity' => 'Số lượng',
            'winning_price' => 'Giá trúng thầu',
        ];
    }
}
