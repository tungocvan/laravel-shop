<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\UserBidAwardWorkspace;
use ReflectionClass;
use Tests\TestCase;

class PharmaBidAwardsCapabilityTest extends TestCase
{
    public function test_bid_award_routes_are_read_only_and_feature_guarded(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $index = Route::getRoutes()->getByName('client.pharma.bid-awards');
        $this->assertNotNull($index);
        $this->assertSame('GET', $index->methods()[0]);
        $this->assertSame('apps/pharma/bid-awards', $index->uri());
        $this->assertContains('auth:web', $index->gatherMiddleware());
        $this->assertContains('client.application:pharma', $index->gatherMiddleware());
        $this->assertContains('client.feature:pharma,bid-awards', $index->gatherMiddleware());
        $this->assertNotContains('auth:admin', $index->gatherMiddleware());

        $show = Route::getRoutes()->getByName('client.pharma.bid-awards.show');
        $this->assertNotNull($show);
        $this->assertSame('GET', $show->methods()[0]);
        $this->assertSame('apps/pharma/bid-awards/{scope}', $show->uri());
        $this->assertContains('client.feature:pharma,bid-awards', $show->gatherMiddleware());
    }

    public function test_bid_award_workspace_exposes_global_results_with_user_scoped_context(): void
    {
        $service = new ReflectionClass(UserBidAwardWorkspace::class);
        foreach (['browseResults', 'findResult', 'products'] as $method) {
            $this->assertTrue($service->hasMethod($method));
            $this->assertTrue($service->getMethod($method)->isPublic());
        }

        $source = file_get_contents(base_path('Modules/Pharma/Services/UserBidAwardWorkspace.php'));
        $this->assertStringContainsString("->where('scoped_assignments.user_id', \$userId)", $source);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::STATUS_ACTIVE', $source);
        $this->assertStringContainsString('DrugBidAwardAllocation::STATUS_ACTIVE', $source);
        $this->assertStringContainsString("MAX(awards.contract_duration_months) as contract_duration_months", $source);
        $this->assertStringContainsString("MAX(awards.contract_period_text) as contract_period_text", $source);
        $this->assertStringContainsString("'scope_key' => sha1(\$identity)", $source);
        $this->assertStringNotContainsString('auth()', $source);
        $this->assertStringNotContainsString('auth(', $source);
    }

    public function test_bid_award_scope_matches_the_canonical_commercial_assignment_contract(): void
    {
        $bidAwards = file_get_contents(base_path('Modules/Pharma/Services/UserBidAwardWorkspace.php'));
        $commercial = file_get_contents(base_path('Modules/Pharma/Services/UserCommercialHospitalWorkspace.php'));

        $commercialClauses = [
            "->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')",
            "->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id')",
            "->where('workspace_assignments.user_id', \$userId)",
            "->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)",
            "->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)",
        ];
        foreach ($commercialClauses as $clause) {
            $this->assertStringContainsString($clause, $commercial);
        }

        $bidContextClauses = [
            "->on('scoped_allocations.drug_bid_award_id', '=', 'scoped_assignments.drug_bid_award_id')",
            "->on('scoped_allocations.partner_id', '=', 'scoped_assignments.partner_id')",
            "->where('scoped_assignments.user_id', \$userId)",
            "->where('scoped_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)",
            "->where('scoped_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)",
        ];
        foreach ($bidContextClauses as $clause) {
            $this->assertStringContainsString($clause, $bidAwards);
        }

        $this->assertStringContainsString("DB::table('pharma_drug_bid_awards as source_awards')", $bidAwards);
        $this->assertStringContainsString("'my_allocated_quantity'", $bidAwards);
        $this->assertStringContainsString("'my_hospitals_count'", $bidAwards);
        $this->assertStringContainsString('return $this->globalRowsWithUserContext($userId)', $bidAwards);
        $this->assertStringContainsString('filterOptions', $bidAwards);
        $this->assertStringContainsString("'allocation_ready'", $bidAwards);
        $this->assertStringContainsString("'commercial_ready'", $bidAwards);
        $this->assertStringContainsString('allocated_product_count', $bidAwards);
        $this->assertStringContainsString('commercial_product_count', $bidAwards);
        $this->assertStringNotContainsString("->where('awards.user_id'", $bidAwards);
        $this->assertStringNotContainsString('auth()', $bidAwards);
    }

    public function test_client_bid_awards_consumes_managed_presentation_and_mobile_workspace(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-awards.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-show.blade.php'));

        $this->assertStringContainsString("'route' => 'client.pharma.bid-awards'", $manifest);
        $this->assertStringContainsString("'eyebrow' => 'Bid Awards'", $manifest);
        $this->assertStringContainsString("'page_title' => 'Kết quả trúng thầu'", $manifest);
        $this->assertStringContainsString("'page_description' =>", $manifest);

        $this->assertStringContainsString('UserBidAwardWorkspace $workspace', $controller);
        $this->assertStringContainsString('$workspace->browseResults(', $controller);
        $this->assertStringContainsString("investor: \$validated['investor'] ?? null", $controller);
        $this->assertStringContainsString("medicine: \$validated['medicine'] ?? null", $controller);
        $this->assertStringContainsString("valueSort: \$validated['value_sort'] ?? null", $controller);
        $this->assertStringContainsString("businessSetup: \$validated['business_setup'] ?? null", $controller);
        $this->assertStringContainsString("'filterOptions' => \$workspace->filterOptions()", $controller);
        $this->assertStringContainsString('$workspace->findResult((int) $user->id, $scope)', $controller);
        $this->assertStringContainsString('abort_if($result === null, 404)', $controller);
        $this->assertStringContainsString('$settings->featurePresentation($application[\'key\'], $feature)', $controller);

        foreach ([$list, $detail] as $view) {
            $this->assertStringContainsString("\$featurePresentation['eyebrow']", $view);
            $this->assertStringContainsString("\$featurePresentation['page_title']", $view);
            $this->assertStringNotContainsString('Admin::', $view);
        }

        $this->assertStringContainsString('active:scale-[0.985]', $list);
        $this->assertStringContainsString('Xem thêm kết quả', $list);
        $this->assertStringContainsString('DOMParser', $list);
        $this->assertStringContainsString('Danh sách kết quả trúng thầu', $list);
        $this->assertStringContainsString('kết quả trúng thầu · phần được giao sẽ được đánh dấu riêng', $list);
        $this->assertStringContainsString('Bộ lọc nâng cao', $list);
        $this->assertStringContainsString('Chủ đầu tư', $list);
        $this->assertStringContainsString('Sản phẩm', $list);
        $this->assertStringContainsString('Giá trị', $list);
        $this->assertStringContainsString('Thiết lập kinh doanh', $list);
        $this->assertStringContainsString('Phân bổ:', $list);
        $this->assertStringContainsString('CSKD:', $list);
        $this->assertStringNotContainsString('SP của tôi', $list);
        $this->assertStringNotContainsString('SL của tôi', $list);
        $this->assertStringContainsString('Giá trị KQLCNT', $list);
        $this->assertStringContainsString('Còn {{ str_pad', $list);
        $this->assertStringContainsString('md:grid-cols-2 xl:grid-cols-3', $list);
        $this->assertStringContainsString('\\Carbon\\Carbon::parse', $list);
        $this->assertStringNotContainsString('CarbonCarbon::parse', $list);
        $this->assertStringContainsString("setTimeout(()=>form.requestSubmit(),350)", $list);
        $this->assertStringContainsString('Trong phạm vi tôi phụ trách', $list);
        $this->assertStringContainsString('md:grid-cols-2 xl:grid-cols-3', $list);
        $this->assertStringNotContainsString('BV của tôi', $detail);
        $this->assertStringContainsString('KQLCNT', $detail);
        $this->assertStringContainsString('Xem thêm sản phẩm', $detail);
        $this->assertStringContainsString('Giá trúng thầu', $detail);
        $this->assertStringNotContainsString('SL của tôi', $detail);
        $this->assertStringNotContainsString('{{ $size }} / trang', $list);
    }
    public function test_bid_award_mutation_workflow_is_permissioned_sequenced_and_mobile_first(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $allocation = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $policy = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-commercial-policy.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-show.blade.php'));

        $this->assertStringContainsString('client.pharma.bid-awards.allocate', $manifest);
        $this->assertStringContainsString('client.pharma.bid-awards.commercial-policy', $manifest);
        $this->assertStringContainsString("Route::post('/bid-awards/{scope}/allocation/setup'", $routes);
        $this->assertStringContainsString("Route::post('/bid-awards/{scope}/allocation'", $routes);
        $this->assertStringContainsString("Route::post('/bid-awards/{scope}/commercial-policy'", $routes);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.bid-awards.allocate')", $controller);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.bid-awards.commercial-policy')", $controller);
        $this->assertStringContainsString('DrugBidAwardAllocationService', $workflow);
        $this->assertStringContainsString('DrugBidAwardCommercialPolicyService', $workflow);
        $this->assertStringContainsString('Cần hoàn tất phân bổ số lượng trước', $workflow);
        $this->assertStringContainsString('Sản phẩm phải được phân bổ số lượng trước', $workflow);
        $this->assertStringContainsString('Thiết lập chung', $allocation);
        $this->assertStringContainsString('Bước 1 · Phạm vi', $allocation);
        $this->assertStringContainsString('Bước 2 · Cơ sở nhận phân bổ', $allocation);
        $this->assertStringContainsString('Bước 3 · Kiểm tra trước khi lưu', $allocation);
        $this->assertStringContainsString('data-review-checkbox', $allocation);
        $this->assertStringContainsString('@checked(in_array((int)$facility->id,$draftFacilityIds,true))', $allocation);
        $this->assertStringContainsString('Lưu thiết lập phân bổ', $allocation);
        $this->assertStringContainsString('Phân bổ sản phẩm', $allocation);
        $this->assertStringNotContainsString('pharma.bid_awards.$scope.hospitals', $controller);
        $this->assertStringContainsString('saveDistributionSetup', $workflow);
        $this->assertStringContainsString('DrugBidAwardDistributionScopeService', $workflow);
        $this->assertStringContainsString("str(\$hospital->name)->lower()", $allocation);
        $this->assertStringNotContainsString('IlluminateSupportStr', $allocation);
        $this->assertStringNotContainsString('Illuminate\\Support\\Str::lower', $allocation);
        $this->assertStringContainsString('md:grid-cols-2', $allocation);
        $this->assertStringContainsString('sticky bottom-3', $allocation);
        $this->assertStringContainsString('Thiết lập chính sách kinh doanh', $policy);
        $this->assertStringContainsString('Cần hoàn tất phân bổ số lượng trước.', $detail);
        $this->assertStringContainsString('active:scale-[.985]', $detail);
    }

}
