<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaKqlcntExcelRemovalContractTest extends TestCase
{
    public function test_excel_removal_is_guarded_by_provenance_and_downstream_documents(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/KqlcntExcelAwardRemovalService.php'));
        $this->assertIsString($service);
        foreach ([
            "lockForUpdate()", "DB::transaction(", "line->source !== 'excel'",
            'pharma_inventory_issue_items', 'pharma_inventory_issue_commissions',
            'pharma_inventory_issue_deferred_supplies', 'pharma_drug_bid_award_contracts',
            'pharma_price_list_item_bid_evidence', 'pharma_drug_bid_award_sources',
            'assertEmptySetup', 'result_key',
        ] as $guard) {
            $this->assertStringContainsString($guard, $service, $guard);
        }
    }

    public function test_canonical_routes_use_separate_authorized_mutations(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertStringContainsString("Route::post('/kqlcnt-awards/{awardItem}/reset-excel-setup'", $routes);
        $this->assertStringContainsString("Route::delete('/kqlcnt-awards/{awardItem}/excel'", $routes);
        $this->assertStringContainsString('public function resetExcelSetup(', $controller);
        $this->assertStringContainsString('public function deleteExcel(', $controller);
        $this->assertStringContainsString('client.pharma.kqlcnt-awards.import', $controller);
        $this->assertStringContainsString('hash_equals(', $controller);
    }

    public function test_delete_ui_requires_excel_provenance_and_explicit_confirmation(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-award-detail.blade.php'));
        $this->assertStringContainsString("line->source === 'excel'", $view);
        $this->assertStringContainsString("@method('DELETE')", $view);
        $this->assertStringContainsString('name="confirm_notice"', $view);
        $this->assertStringContainsString('name="acknowledge"', $view);
    }
}
