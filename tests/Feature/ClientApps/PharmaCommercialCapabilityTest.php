<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\UserCommercialHospitalWorkspace;
use ReflectionClass;
use Tests\TestCase;

class PharmaCommercialCapabilityTest extends TestCase
{
    public function test_commercial_routes_are_read_only_and_feature_guarded(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $route = Route::getRoutes()->getByName('client.pharma.commercial');
        $this->assertNotNull($route);
        $this->assertSame('GET', $route->methods()[0]);
        $this->assertSame('apps/pharma/commercial', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:pharma', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,commercial', $route->gatherMiddleware());
        $this->assertNotContains('auth:admin', $route->gatherMiddleware());

        $detail = Route::getRoutes()->getByName('client.pharma.commercial.hospitals.show');
        $this->assertNotNull($detail);
        $this->assertSame('GET', $detail->methods()[0]);
        $this->assertSame('apps/pharma/commercial/hospitals/{hospital}', $detail->uri());
        $this->assertContains('client.feature:pharma,commercial', $detail->gatherMiddleware());
    }

    public function test_workspace_scopes_hospitals_and_counts_to_active_user_assignments_and_allocations(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/UserCommercialHospitalWorkspace.php'));

        $this->assertStringContainsString('public function browseHospitals(', $service);
        $this->assertStringContainsString('public function summary(int $userId): array', $service);
        $this->assertStringContainsString('public function findHospital(int $userId, int $partnerId): ?Partner', $service);
        $this->assertStringContainsString("->where('workspace_assignments.user_id', \$userId)", $service);
        $this->assertStringContainsString("DrugBidAwardManagementAssignment::STATUS_ACTIVE", $service);
        $this->assertStringContainsString("DrugBidAwardAllocation::STATUS_ACTIVE", $service);
        $this->assertStringContainsString("whereColumn('workspace_allocations.partner_id', 'workspace_assignments.partner_id')", $service);
        $this->assertStringContainsString('COUNT(DISTINCT product_assignments.drug_bid_award_id)', $service);
        $this->assertStringNotContainsString('auth()', $service);
    }

    public function test_client_commercial_surface_uses_scoped_service_and_mobile_first_hospital_cards(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commercial.blade.php'));

        $this->assertStringContainsString('UserCommercialHospitalWorkspace $workspace', $controller);
        $this->assertStringContainsString('$workspace->browseHospitals(', $controller);
        $this->assertStringContainsString('$workspace->summary((int) $user->id)', $controller);
        $this->assertStringContainsString('$workspace->findHospital((int) $user->id, $hospital)', $controller);
        $this->assertStringContainsString('abort_if($scopedHospital === null, 404)', $controller);

        $this->assertStringContainsString('Công việc bệnh viện của tôi', $view);
        $this->assertStringContainsString('Danh sách chỉ gồm bệnh viện và sản phẩm trúng thầu đang được phân công', $view);
        $this->assertStringContainsString('@foreach([25, 50, 100] as $size)', $view);
        $this->assertStringContainsString('{{ $size }} / trang', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString('min-w-0', $view);
        $this->assertStringContainsString('h-11 w-11', $view);
        $this->assertStringContainsString("route('client.pharma.commercial.hospitals.show'", $view);
        $this->assertStringNotContainsString('Admin::', $view);
    }

    public function test_workspace_is_a_public_reusable_pharma_read_contract(): void
    {
        $service = new ReflectionClass(UserCommercialHospitalWorkspace::class);
        $this->assertTrue($service->hasMethod('browseHospitals'));
        $this->assertTrue($service->hasMethod('summary'));
        $this->assertTrue($service->hasMethod('findHospital'));
        $this->assertTrue($service->getMethod('browseHospitals')->isPublic());
    }
}
