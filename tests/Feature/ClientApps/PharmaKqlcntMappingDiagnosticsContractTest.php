<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntCanonicalProductScope;
use Tests\TestCase;

class PharmaKqlcntMappingDiagnosticsContractTest extends TestCase
{
    public function test_mapping_diagnostics_never_writes_or_auto_links(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntMappingDiagnostics.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString("where('source_type', 'muasamcong')", $source);
        $this->assertStringContainsString("whereIn('source_id', \$ids)", $source);
        $this->assertStringContainsString("where('contractor_code', \$line->contractor_code)", $source);
        $this->assertStringContainsString("'status' => \$status", $source);
        foreach (['->insert(', '->update(', '->delete(', '->save(', 'updateOrCreate(', 'firstOrCreate('] as $write) {
            $this->assertStringNotContainsString($write, $source);
        }
    }

    public function test_preview_only_displays_reconciliation_evidence(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $this->assertStringContainsString("'mappingDiagnostics' => \$diagnostics->inspect(\$awardItem)", $controller);
        $this->assertStringContainsString("mappingDiagnostics['missing_count']", $view);
        $this->assertStringContainsString('không tự động liên kết', $view);
        $this->assertStringNotContainsString('<form', $view);
    }

    public function test_missing_contractor_code_requires_exact_contractor_name(): void
    {
        $line = (object) ['id' => 22, 'notify_no' => 'TBMT', 'contractor_code' => '', 'contractor_name' => 'Nhà thầu A'];
        $wrong = (object) ['id' => 31, 'source_id' => 22, 'bidding_notice_code' => 'TBMT', 'contractor_code' => '', 'winning_company_name' => 'Nhà thầu B'];

        $this->expectException(ValidationException::class);
        app(KqlcntCanonicalProductScope::class)->resolve(collect([$line]), collect([$wrong]));
    }

    public function test_missing_contractor_code_accepts_exact_contractor_name(): void
    {
        $line = (object) ['id' => 22, 'notify_no' => 'TBMT', 'contractor_code' => '', 'contractor_name' => 'Nhà thầu A'];
        $match = (object) ['id' => 31, 'source_id' => 22, 'bidding_notice_code' => 'TBMT', 'contractor_code' => '', 'winning_company_name' => 'Nhà thầu A'];

        $this->assertSame([31], app(KqlcntCanonicalProductScope::class)->resolve(collect([$line]), collect([$match])));
    }
}
