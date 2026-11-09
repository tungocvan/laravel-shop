<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Pharma\Services\KqlcntLegacyWorkflowBridge;
use ReflectionMethod;
use Tests\TestCase;

class PharmaKqlcntHistoricalBidAwardBridgeContractTest extends TestCase
{
    public function test_fallback_requires_exact_notice_contractor_medicine_quantity_and_price(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('verifiedExistingProductIds($awardItemId)', $source);
        $this->assertStringContainsString("where('bidding_notice_code', \$notice)", $source);
        $this->assertStringContainsString("where('contractor_code', \$line->contractor_code)", $source);
        $this->assertStringContainsString("where('winning_company_name', \$contractorName)", $source);
        $this->assertStringContainsString("where('medicine_name', \$medicineName)", $source);
        $this->assertStringContainsString("whereNull('source_id')", $source);
        $this->assertStringContainsString('DrugBidAward::SOURCE_MANUAL', $source);
        $this->assertStringContainsString('$matches->count() !== 1', $source);
        $this->assertStringContainsString('in_array($id, $resolved, true)', $source);
        $this->assertStringContainsString('if ($ids !== $legacyIds)', $source);
    }

    public function test_fallback_is_read_only_and_does_not_create_mapping_or_new_routes(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        foreach (['->save(', '->update(', '->insert(', '->delete(', 'updateOrCreate(', 'firstOrCreate('] as $mutation) {
            $this->assertStringNotContainsString($mutation, $source);
        }

        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertStringNotContainsString("Route::post('/kqlcnt-awards/{awardItem}/allocation'", $routes);
    }

    public function test_decimal_comparison_is_normalized_and_null_safe(): void
    {
        $method = new ReflectionMethod(KqlcntLegacyWorkflowBridge::class, 'sameDecimal');
        $bridge = app(KqlcntLegacyWorkflowBridge::class);
        $this->assertTrue($method->invoke($bridge, '2500.0000', '2500'));
        $this->assertTrue($method->invoke($bridge, '125.5000', '125.5'));
        $this->assertFalse($method->invoke($bridge, '2500.0000', '2501.0000'));
        $this->assertFalse($method->invoke($bridge, null, '2500.0000'));
    }
}
