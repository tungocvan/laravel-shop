<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntExcelBootstrapContractTest extends TestCase
{
    public function test_bootstrap_is_explicit_post_and_requires_existing_allocation_permission(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $this->assertStringContainsString("Route::post('/kqlcnt-awards/{awardItem}/bootstrap-excel'", $routes);
        $this->assertStringContainsString("'client.pharma.bid-awards.allocate'", $controller);
        $this->assertStringContainsString("'acknowledge' => ['required', 'accepted']", $controller);
        $this->assertStringContainsString('@csrf', $blade);
        $this->assertStringContainsString('$canBootstrapExcel', $blade);
        $this->assertStringContainsString("route('client.pharma.kqlcnt-awards.bootstrap-excel'", $blade);
    }

    public function test_only_entire_new_excel_notice_is_bootstrapped_atomically(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/KqlcntExcelAwardBootstrapService.php'));
        foreach ([
            "DB::transaction(",
            "->lockForUpdate()",
            "where('bidding_notice_code', \$notice)->exists()",
            "KqlcntAwardItem::query()->where('notify_no', \$notice)",
            "(string) \$line->source !== 'excel'",
            "'source_type' => DrugBidAward::SOURCE_MUASAMCONG",
            "'source_id' => \$line->id",
            "'created_by' => \$userId",
        ] as $expected) {
            $this->assertStringContainsString($expected, $service);
        }
        $this->assertStringNotContainsString('allocations()->create(', $service);
        $this->assertStringNotContainsString('commercialPolicy()->create(', $service);
    }

    public function test_existing_bid_awards_routes_remain_available(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertStringContainsString("name('bid-awards.allocation')", $routes);
        $this->assertStringContainsString("name('bid-awards.commercial-policy')", $routes);
    }
}
