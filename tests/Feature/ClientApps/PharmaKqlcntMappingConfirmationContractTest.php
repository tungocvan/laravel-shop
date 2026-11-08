<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Pharma\Services\KqlcntMappingConfirmation;
use ReflectionMethod;
use Tests\TestCase;

class PharmaKqlcntMappingConfirmationContractTest extends TestCase
{
    public function test_mapping_requires_permission_acknowledgement_and_csrf_protection(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));

        $this->assertStringContainsString("userCan(\$user, 'client.pharma.bid-awards.allocate')", $controller);
        $this->assertStringContainsString("'acknowledge' => ['required', 'accepted']", $controller);
        $this->assertStringContainsString("Route::post('/kqlcnt-awards/{awardItem}/mapping/confirm'", $routes);
        $this->assertStringContainsString("client.feature:pharma,kqlcnt-awards", $routes);
        $this->assertStringContainsString('@csrf', $view);
        $this->assertStringContainsString('@if($mode === \'allocation\' && $canConfirmMapping)', $view);
        $this->assertStringContainsString('name="acknowledge"', $view);
        $this->assertStringNotContainsString('bid-awards.allocation.setup', $view);
    }

    public function test_mapping_is_transactional_unique_and_rechecks_exact_legacy_scope(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntMappingConfirmation.php'));
        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('verifiedMappingCandidateIds($awardItemId)', $source);
        $this->assertStringContainsString('$legacyIds !== $candidateIds', $source);
        $this->assertStringContainsString('$matches->count() !== 1', $source);
        $this->assertStringContainsString("->where('source_id', \$line->id)", $source);
        $this->assertStringContainsString('DrugBidAward::SOURCE_MANUAL', $source);
        $this->assertStringContainsString('DrugBidAward::SOURCE_MUASAMCONG', $source);
        $this->assertStringContainsString('manual_note', $source);
    }

    public function test_quantity_and_price_comparison_is_null_safe(): void
    {
        $service = app(KqlcntMappingConfirmation::class);
        $method = new ReflectionMethod(KqlcntMappingConfirmation::class, 'decimalEqual');
        $this->assertTrue($method->invoke($service, '2500.0000', '2500'));
        $this->assertFalse($method->invoke($service, null, '2500'));
        $this->assertFalse($method->invoke($service, '2500', '2501'));
    }
}
