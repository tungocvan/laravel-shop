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

        $this->assertStringContainsString('public function assignedUsers(): Collection', $service);
        $this->assertStringContainsString("->whereColumn('workspace_assignments.user_id', 'users.id')", $service);
        $this->assertStringContainsString('public function assignedAwardScopes(int $userId): Collection', $service);
        $this->assertStringContainsString("sha1(\$identity)", $service);
        $this->assertStringContainsString("'tbmt:'.\$award->bidding_notice_code", $service);
        $this->assertStringContainsString("'decision:'.\$award->decision_number", $service);
        $this->assertStringContainsString('public function browseHospitals(', $service);
        $this->assertStringContainsString('public function summary(int $userId, ?object $awardScope = null): array', $service);
        $this->assertStringContainsString('public function findHospital(int $userId, int $partnerId, ?object $awardScope = null): ?Partner', $service);
        $this->assertStringContainsString('allocatedAwardValueQuery', $service);
        $this->assertStringContainsString('allocated_quantity * COALESCE(awards.winning_price, awards.unit_price, 0)', $service);
        $this->assertStringContainsString("->where('workspace_assignments.user_id', \$userId)", $service);
        $this->assertStringContainsString("DrugBidAwardManagementAssignment::STATUS_ACTIVE", $service);
        $this->assertStringContainsString("DrugBidAwardAllocation::STATUS_ACTIVE", $service);
        $this->assertStringContainsString("'workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id'", $service);
        $this->assertStringContainsString('COUNT(DISTINCT product_assignments.drug_bid_award_id)', $service);
        $this->assertStringContainsString('public function assignedProducts(', $service);
        $this->assertStringContainsString("->where('workspace_assignments.partner_id', \$partnerId)", $service);
        $this->assertStringContainsString("'pharma_drug_bid_award_product_policies as product_policies'", $service);
        $this->assertStringContainsString('COALESCE(workspace_allocations.commercial_policy_percentage, product_policies.commission_percentage) as effective_policy_percentage', $service);
        $this->assertStringContainsString('public function commercialContext(int $userId, int $partnerId, int $awardId, bool $includeSupplierPricing = false): array', $service);
        $this->assertStringContainsString("'import_price' => \$includePricing ? \$tracking->import_price : null", $service);
        $this->assertStringContainsString("'pricing_visible' => \$includePricing", $service);
        $this->assertStringContainsString('private readonly PriceResolver $priceResolver', $service);
        $this->assertStringContainsString("->where('medicine_id', \$medicineId)", $service);
        $this->assertStringContainsString("if (\$variants->count() !== 1)", $service);
        $this->assertStringContainsString("if (\$packages->count() > 1)", $service);
        $this->assertStringContainsString("->where('status', 'active')", $service);
        $this->assertStringContainsString("->orderByDesc('working_date')", $service);
        $this->assertStringNotContainsString('auth()', $service);
    }

    public function test_client_commercial_surface_uses_scoped_service_and_mobile_first_hospital_cards(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commercial.blade.php'));
        $foundation = file_get_contents(base_path('resources/js/clientportal/native-interactions.js'));

        $this->assertStringContainsString('UserCommercialHospitalWorkspace $workspace', $controller);
        $this->assertStringContainsString("'client.pharma.commercial.view-team'", $controller);
        $this->assertStringContainsString("'manager_user_id' => ['nullable', 'integer']", $controller);
        $this->assertStringContainsString("'award_scope' => ['nullable', 'string', 'size:40']", $controller);
        $this->assertStringContainsString('$workspace->assignedAwardScopes($targetUserId)', $controller);
        $this->assertStringContainsString("firstWhere('scope_key', \$awardScopeKey)", $controller);
        $this->assertStringContainsString('abort_if($awardScope === null, 404)', $controller);
        $this->assertStringContainsString('$workspace->assignedUsers()', $controller);
        $this->assertStringContainsString('userId: $targetUserId', $controller);
        $this->assertStringContainsString('$workspace->browseHospitals(', $controller);
        $this->assertStringContainsString('$workspace->summary($targetUserId, $awardScope)', $controller);
        $this->assertStringContainsString('$workspace->findHospital($targetUserId, $hospital, $awardScope)', $controller);
        $this->assertStringContainsString('abort_if($scopedHospital === null, 404)', $controller);
        $this->assertStringContainsString('$workspace->assignedProducts(', $controller);
        $this->assertStringContainsString('partnerId: (int) $scopedHospital->id', $controller);
        $this->assertStringContainsString('perPage: 20', $controller);

        $this->assertStringContainsString("\$featurePresentation['eyebrow']", $view);
        $this->assertStringContainsString("\$featurePresentation['page_title']", $view);
        $this->assertStringContainsString("\$featurePresentation['page_description']", $view);
        $this->assertStringContainsString("\$settings->featurePresentation(\$application['key'], \$commercialFeature)", $controller);
        $this->assertStringContainsString('Nhân viên phụ trách', $view);
        $this->assertStringContainsString('Chủ đầu tư / Kết quả trúng thầu', $view);
        $this->assertStringContainsString('Chọn kết quả trúng thầu', $view);
        $this->assertStringContainsString('name="award_scope"', $view);
        $this->assertStringContainsString('Tổng giá trị trúng thầu', $view);
        $this->assertStringContainsString('allocated_award_value', $view);
        $this->assertStringContainsString('SKU', $view);
        $this->assertStringNotContainsString('Tỉnh {{ $hospital->province_code }}', $view);
        $this->assertStringContainsString('manager_user_id', $view);
        $this->assertStringContainsString('active:scale-[0.985]', $view);
        $this->assertStringContainsString('motion-reduce:transform-none', $view);
        $this->assertStringContainsString('commercial-navigation-feedback', $view);
        $this->assertStringContainsString("array_filter(['hospital' => \$hospital->id, 'manager_user_id' => \$managerUserId, 'award_scope' => \$awardScopeKey])", $view);
        $this->assertStringNotContainsString('>Commercial Workspace</p>', $view);
        $this->assertStringNotContainsString(" : 'Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.'", $view);
        $this->assertStringContainsString('Xem thêm bệnh viện', $view);
        $this->assertStringContainsString('commercial-load-more', $view);
        $this->assertStringContainsString('data-commercial-item', $view);
        $this->assertStringContainsString('data-pwa-load-more', $view);
        $this->assertStringContainsString('DOMParser', $foundation);
        $this->assertStringNotContainsString('{{ $size }} / trang', $view);
        $this->assertStringContainsString('data-pwa-search-clear-button="#commercial-hospital-search-input"', $view);
        $this->assertStringContainsString('aria-label="Xóa tìm kiếm bệnh viện"', $view);
        $this->assertStringContainsString('[data-pwa-search-clear-button]', $foundation);
        $this->assertStringContainsString('min-w-0', $view);
        $this->assertStringContainsString('h-11 w-11', $view);
        $this->assertStringContainsString("route('client.pharma.commercial.hospitals.show'", $view);
        $this->assertStringNotContainsString('Admin::', $view);
    }

    public function test_commercial_hospital_detail_shows_assigned_bid_and_effective_policy_data(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commercial-hospital-show.blade.php'));

        $this->assertStringContainsString('Danh sách sản phẩm', $view);
        $this->assertStringContainsString('name="award_scope"', $view);
        $this->assertStringContainsString("'award_scope' => \$awardScopeKey", $view);
        $this->assertStringContainsString('Tổng giá trị trúng thầu', $view);
        $this->assertStringContainsString('Tên thuốc, hoạt chất, số đăng ký...', $view);
        $this->assertStringContainsString('Xem thêm sản phẩm', $view);
        $this->assertStringContainsString('commercial-product-load-more', $view);
        $this->assertStringContainsString('data-commercial-product', $view);
        $this->assertStringNotContainsString('{{ $size }} / trang', $view);
        $this->assertStringContainsString('Giá trúng thầu', $view);
        $this->assertStringContainsString('SL phân bổ', $view);
        $this->assertStringContainsString('SL trúng thầu', $view);
        $this->assertStringContainsString('Chính sách hiệu lực', $view);
        $this->assertStringContainsString("'Theo bệnh viện' : 'Theo sản phẩm'", $view);
        $this->assertStringContainsString('effective_from', $view);
        $this->assertStringContainsString('effective_until', $view);
        $this->assertStringContainsString('Giá bán hiện hành', $view);
        $this->assertStringContainsString('Bảng giá bệnh viện', $view);
        $this->assertStringContainsString('Bảng giá chung', $view);
        $this->assertStringContainsString('Điều kiện NCC hiện hành', $view);
        $this->assertStringContainsString('Giá vốn NCC', $view);
        $this->assertStringContainsString('Giá vốn tính toán', $view);
        $this->assertStringNotContainsString('Admin::', $view);
    }

    public function test_database_price_resolver_qualifies_item_columns_after_join(): void
    {
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/DatabasePriceResolver.php'));

        $this->assertStringContainsString("->where('pharma_price_list_items.status', 'active')", $resolver);
        $this->assertStringContainsString("->where('pharma_price_list_items.medicine_variant_id', \$variantId)", $resolver);
        $this->assertStringContainsString("->where('pharma_price_list_items.identity_key'", $resolver);
        $this->assertStringContainsString("whereNull('pharma_price_list_items.effective_from')", $resolver);
        $this->assertStringContainsString("whereNull('pharma_price_list_items.effective_to')", $resolver);
        $this->assertStringNotContainsString("->where('status', 'active')", $resolver);
    }

    public function test_workspace_is_a_public_reusable_pharma_read_contract(): void
    {
        $service = new ReflectionClass(UserCommercialHospitalWorkspace::class);
        $this->assertTrue($service->hasMethod('assignedUsers'));
        $this->assertTrue($service->hasMethod('assignedAwardScopes'));
        $this->assertTrue($service->hasMethod('browseHospitals'));
        $this->assertTrue($service->hasMethod('summary'));
        $this->assertTrue($service->hasMethod('findHospital'));
        $this->assertTrue($service->hasMethod('assignedProducts'));
        $this->assertTrue($service->hasMethod('commercialContext'));
        $this->assertTrue($service->getMethod('browseHospitals')->isPublic());
        $this->assertTrue($service->getMethod('assignedProducts')->isPublic());
    }
}
