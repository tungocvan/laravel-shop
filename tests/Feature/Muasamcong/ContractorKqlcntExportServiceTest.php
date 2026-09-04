<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\ContractorKqlcntWorkbookExport;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorKqlcntExportService;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Tests\TestCase;

class ContractorKqlcntExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_exports_execution_period_in_overview_and_effect_period_in_contract_sheet(): void
    {
        Excel::fake();
        $search = app(ContractorSearchArchiveService::class)->store(
            'vn0314492345',
            'CÔNG TY TNHH INAFO VIỆT NAM',
            '2021-01-01',
            null,
            [
                'reported_total' => 1,
                'total_pages' => 1,
                'items' => [[
                    'id' => '1',
                    'notifyNo' => 'IB2500317380',
                    'bidName' => 'Gói thầu Thuốc Generic',
                    'createdDate' => '2026-08-01T00:00:00',
                ]],
            ]
        );

        KqlcntRecord::create([
            'notify_no' => 'IB2500317380',
            'contractor_code' => 'vn0314492345',
            'contractor_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
            'bid_name' => 'Gói thầu Thuốc Generic',
            'contract_period' => 730,
            'contract_period_unit' => 'D',
            'contract_period_text' => '730 ngày',
            'effect_frame_period' => 'Kể từ ngày ký đến hết 06/02/2028',
            'contracts' => [[
                'contractNo' => 'HD-01',
                'contractorName' => 'CÔNG TY TNHH INAFO VIỆT NAM',
            ]],
            'all_winners' => [],
            'verified_lots' => [],
            'tbmt_raw' => [],
            'contracts_raw' => [],
            'data_source' => 'api',
            'synced_at' => now(),
        ]);

        app(ContractorKqlcntExportService::class)->download($search, ['IB2500317380']);

        Excel::assertDownloaded(function (string $fileName, ContractorKqlcntWorkbookExport $export): bool {
            $sheets = $export->sheets();
            $overview = $sheets[0];
            $contracts = $sheets[2];

            $this->assertSame('Tong_quan_KQLCNT', $overview->title());
            $this->assertContains('Thời gian thực hiện gói thầu', $overview->headings());
            $this->assertContains('Thời hạn hiệu lực', $overview->headings());
            $this->assertSame(730, $overview->array()[0][10]);
            $this->assertSame('D', $overview->array()[0][11]);
            $this->assertSame('730 ngày', $overview->array()[0][12]);
            $this->assertSame('Kể từ ngày ký đến hết 06/02/2028', $overview->array()[0][13]);

            $this->assertSame('Hop_dong', $contracts->title());
            $this->assertContains('Thời hạn hiệu lực', $contracts->headings());
            $this->assertSame('Kể từ ngày ký đến hết 06/02/2028', $contracts->array()[0][8]);

            return str_starts_with($fileName, 'KQLCNT-vn0314492345-search-');
        });
    }
}
