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
        $this->assertStringContainsString("Route::post('/bid-awards/{scope}/allocation/hospitals/{partner}'", $routes);
        $this->assertStringNotContainsString("Route::post('/bid-awards/{scope}/allocation',", $routes);
        $this->assertStringContainsString("Route::post('/bid-awards/{scope}/commercial-policy'", $routes);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.bid-awards.allocate')", $controller);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.bid-awards.commercial-policy')", $controller);
        $this->assertStringContainsString('DrugBidAwardAllocationService', $workflow);
        $this->assertStringContainsString('DrugBidAwardCommercialPolicyService', $workflow);
        $this->assertStringContainsString('Cần hoàn tất phân bổ số lượng trước', $workflow);
        $this->assertStringContainsString('Sản phẩm phải được phân bổ số lượng trước', $workflow);
        $this->assertStringContainsString('Thiết lập chung', $allocation);
        $this->assertStringContainsString('① Phạm vi & hiệu lực', $allocation);
        $this->assertStringContainsString('② Chọn cơ sở KCB', $allocation);
        $this->assertStringContainsString('③ Kiểm tra & lưu', $allocation);
        $this->assertStringContainsString('data-review-checkbox', $allocation);
        $this->assertStringContainsString('@checked(in_array((int)$facility->id,$draftFacilityIds,true))', $allocation);
        $this->assertStringContainsString('Lưu thiết lập phân bổ', $allocation);
        $this->assertStringContainsString('Bệnh viện nhận phân bổ', $allocation);
        $this->assertStringContainsString('Nhận phân bổ số lượng', $allocation);
        $this->assertStringNotContainsString('pharma.bid_awards.$scope.hospitals', $controller);
        $this->assertStringContainsString('saveDistributionSetup', $workflow);
        $this->assertStringContainsString('DrugBidAwardDistributionScopeService', $workflow);
        $this->assertStringContainsString("str(\$province)->lower()", $allocation);
        $this->assertStringContainsString("str(\$facility->facility_name.' '.\$facility->external_id)->lower()", $allocation);
        $this->assertStringNotContainsString('IlluminateSupportStr', $allocation);
        $this->assertStringNotContainsString('Illuminate\\Support\\Str::lower', $allocation);
        $this->assertGreaterThanOrEqual(3, substr_count($allocation, '<details'));
        $this->assertStringContainsString('md:grid-cols-2', $allocation);
        $this->assertStringContainsString('Thiết lập chính sách kinh doanh', $policy);
        $this->assertStringContainsString('Cần hoàn tất phân bổ số lượng trước.', $detail);
        $this->assertStringContainsString('active:scale-[.985]', $detail);
    }

    public function test_bid_award_allocation_is_collapsible_and_hospital_first(): void
    {
        $routes=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $index=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $hospital=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-hospital-allocation.blade.php'));
        $policy=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-hospital-policy.blade.php'));

        $this->assertGreaterThanOrEqual(3, substr_count($index,'<details'));
        $this->assertStringContainsString('① Phạm vi & hiệu lực',$index);
        $this->assertStringContainsString('② Chọn cơ sở KCB',$index);
        $this->assertStringContainsString('③ Kiểm tra & lưu',$index);
        $this->assertStringContainsString('Bệnh viện nhận phân bổ',$index);
        $this->assertStringContainsString('Nhận phân bổ số lượng',$index);
        $this->assertStringNotContainsString('allocations[',$index);
        $this->assertStringContainsString("allocation/hospitals/{partner}",$routes);
        $this->assertStringContainsString('bidAwardHospitalAllocation',$controller);
        $this->assertStringContainsString('bidAwardHospitalCommercialPolicy',$controller);
        $this->assertStringContainsString('saveHospitalAllocations',$workflow);
        $this->assertStringContainsString('saveHospitalPolicyOverride',$workflow);
        $this->assertStringContainsString('Số lượng bệnh viện này',$hospital);
        $this->assertStringContainsString('Thiết lập CSKD',$hospital);
        $this->assertStringContainsString('CSKD riêng bệnh viện (%)',$policy);
        $this->assertStringContainsString('Chưa phân bổ · CSKD bị khóa',$policy);
    }

    public function test_allocation_dashboard_collapses_sections_and_summarizes_product_quantities(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));

        $this->assertStringContainsString('productAllocationCards', $controller);
        $this->assertStringContainsString('SUM(allocated_quantity) as allocated_quantity', $workflow);
        $this->assertStringContainsString('pwa_remaining_quantity = max($winning - $used, 0)', $workflow);
        $this->assertStringContainsString('pwa_fully_allocated', $workflow);
        $this->assertStringContainsString('Tổng quan phân bổ', $view);
        $this->assertStringContainsString('SL trúng thầu', $view);
        $this->assertStringContainsString('Đã phân bổ', $view);
        $this->assertStringContainsString('Còn lại', $view);
        $this->assertStringContainsString('Đã phân bổ hết', $view);
        $this->assertGreaterThanOrEqual(5, substr_count($view, '<details'));
        $this->assertStringContainsString('Bệnh viện nhận phân bổ', $view);
    }

    public function test_hospital_allocation_formats_quantities_and_policy_shows_default_context(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $index=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $allocation=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-hospital-allocation.blade.php'));
        $policy=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-hospital-policy.blade.php'));

        $this->assertStringNotContainsString('>Đang phân bổ</span>', $index);
        $this->assertStringNotContainsString('>Hoàn tất</span>', $index);
        $this->assertStringContainsString("preg_replace('/[^0-9]/', '', (string)\$value)", $controller);
        $this->assertStringContainsString("'quantities.*'=>['nullable','integer','gt:0']", $controller);
        $this->assertStringContainsString('data-quantity-input', $allocation);
        $this->assertStringContainsString("number_format((float)\$row->allocated_quantity,0,',','.')", $allocation);
        $this->assertStringContainsString("replace(/\\D/g,'')", $allocation);
        $this->assertStringContainsString("toLocaleString('vi-VN')", $allocation);
        $this->assertStringContainsString("'productPolicies'=>\$workflow->productPolicies(\$award)", $controller);
        $this->assertStringContainsString('CSKD gốc', $policy);
        $this->assertStringContainsString('Để trống để dùng chính sách gốc.', $policy);
        $this->assertStringContainsString('saveHospitalPolicyOverride', $workflow);
        $this->assertStringContainsString('commercial_policy_percentage !== null', $policy);
    }

    public function test_base_policy_values_are_compact_and_allocation_overview_filters_incomplete_products(): void
    {
        $policy=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-commercial-policy.blade.php'));
        $allocation=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));

        $this->assertStringContainsString("rtrim(rtrim(number_format((float)\$policy->commission_percentage,4,'.',''),'0'),'.')", $policy);
        $this->assertStringContainsString('data-product-allocation-search', $allocation);
        $this->assertStringContainsString('data-product-allocation-clear', $allocation);
        $this->assertStringContainsString('data-incomplete-toggle', $allocation);
        $this->assertStringContainsString('data-product-allocation-card', $allocation);
        $this->assertStringContainsString('data-incomplete="{{ $product->pwa_fully_allocated', $allocation);
        $this->assertStringContainsString('Phân bổ chưa hết', $allocation);
        $this->assertStringContainsString('Đã phân bổ hết', $allocation);
        $this->assertStringContainsString("card.dataset.incomplete==='1'", $allocation);
        $this->assertStringContainsString("ps?.addEventListener('input',applyProducts)", $allocation);
        $this->assertStringContainsString("pt?.addEventListener('click'", $allocation);
        $this->assertStringContainsString('Không có sản phẩm phù hợp bộ lọc.', $allocation);
    }

    public function test_manager_assignment_starts_with_assignment_mode_and_reuses_canonical_services(): void
    {
        $routes=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));
        $policy=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-commercial-policy.blade.php'));
        $assignment=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-manager-assignment.blade.php'));

        $this->assertStringContainsString("Route::get('/bid-awards/{scope}/manager-assignment'", $routes);
        $this->assertStringContainsString("manager-assignment/single'", $routes);
        $this->assertStringContainsString("manager-assignment/products'", $routes);
        $this->assertStringContainsString('commercialPolicyReady($award)', $controller);
        $this->assertStringContainsString("'mode' => ['nullable', 'in:single,multiple']", $controller);
        $this->assertStringContainsString("\$state['persisted_mode'] !== 'unassigned' ? \$state['persisted_mode'] : \$requestedMode", $controller);
        $this->assertStringContainsString("route('client.pharma.bid-awards.manager-assignment', \$scope)", $controller);
        $this->assertStringContainsString('assignManagerToAllAllocations', $service);
        $this->assertStringContainsString('assignManagerToProductAllocations', $service);
        $this->assertStringContainsString('assignSingleManager', $workflow);
        $this->assertStringContainsString('assignManagerToProducts', $workflow);
        $this->assertStringContainsString("where('is_active', true)", $workflow);
        $this->assertStringContainsString('Hãy hoàn tất chính sách kinh doanh trước khi phân công User quản lý.', $workflow);
        $this->assertStringContainsString("whereIn('pharma_drug_bid_award_allocations.drug_bid_award_id', \$awardIds)", $workflow);
        $this->assertStringContainsString("where('pharma_drug_bid_award_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)", $workflow);

        $this->assertStringContainsString('Cách phân công', $assignment);
        $this->assertStringContainsString('Một User phụ trách toàn bộ', $assignment);
        $this->assertStringContainsString('Nhiều User phụ trách', $assignment);
        $this->assertStringContainsString('@if($assignmentMode)', $assignment);
        $this->assertStringContainsString("data-manager-search", $assignment);
        $this->assertStringContainsString('data-select-all-products', $assignment);
        $this->assertStringContainsString('Bệnh viện × Sản phẩm', $assignment);
        $this->assertStringContainsString('Cần gỡ toàn bộ phân công trước khi chuyển sang cách khác.', $assignment);
        $this->assertStringContainsString('Phân công User quản lý', $policy);
        $this->assertStringContainsString('Lưu & tiếp tục', $policy);
        $this->assertStringNotContainsString('Admin::', $assignment);
        $this->assertStringNotContainsString('wire:', $assignment);
    }

    public function test_manager_assignment_shows_current_owner_and_supports_confirmed_reset(): void
    {
        $routes=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-manager-assignment.blade.php'));

        $this->assertStringContainsString("Route::delete('/bid-awards/{scope}/manager-assignment'", $routes);
        $this->assertStringContainsString('destroyBidAwardManagers', $controller);
        $this->assertStringContainsString("'assignmentSummary' => \$workflow->managementAssignmentSummary(\$award)", $controller);
        $this->assertStringContainsString('managementAssignmentSummary', $workflow);
        $this->assertStringContainsString("->with('user:id,name,email')", $workflow);
        $this->assertStringContainsString('return $this->commercialPolicies->removeAllManagers($award);', $workflow);
        $this->assertStringContainsString('public function removeAllManagers', $service);
        $this->assertStringContainsString('Phân công hiện tại', $view);
        $this->assertStringContainsString("summary->user?->email", $view);
        $this->assertStringContainsString('Thay User phụ trách', $view);
        $this->assertStringContainsString('Gỡ phân công toàn bộ', $view);
        $this->assertStringContainsString('data-remove-managers-modal', $view);
        $this->assertStringContainsString('Phân bổ số lượng và chính sách kinh doanh không bị thay đổi.', $view);
        $this->assertStringContainsString("@method('DELETE')", $view);
        $this->assertStringContainsString("route('client.pharma.bid-awards.manager-assignment.destroy',\$scope)", $view);
        $this->assertStringContainsString('Muốn chuyển sang Nhiều User, hãy gỡ phân công hiện tại trước.', $view);
    }

    public function test_multi_user_assignment_is_user_then_hospital_then_unassigned_products(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-manager-assignment.blade.php'));

        $this->assertStringContainsString("'hospital_id' => ['nullable','integer']", $controller);
        $this->assertStringContainsString("'manager_id' => ['nullable','integer']", $controller);
        $this->assertStringContainsString('managementHospitalCards($award)', $controller);
        $this->assertStringContainsString('unassignedHospitalProducts($award, $hospitalId)', $controller);
        $this->assertStringContainsString('assignManagerToHospitalProducts', $controller);
        $this->assertStringContainsString("'hospital_id' => ['required','integer']", $controller);
        $this->assertStringContainsString('managementHospitalCards', $workflow);
        $this->assertStringContainsString('pwa_management_remaining_count', $workflow);
        $this->assertStringContainsString('pwa_management_complete', $workflow);
        $this->assertStringContainsString('unassignedHospitalProducts', $workflow);
        $this->assertStringContainsString("! \$assignedAwardIds->contains((int) \$product->id)", $workflow);
        $this->assertStringContainsString('assignManagerToHospitalProducts', $workflow);
        $this->assertStringContainsString('count($selectedIds) !== count(array_unique(array_map', $workflow);
        $this->assertStringContainsString('Có sản phẩm đã được User khác phụ trách hoặc không được phân bổ tại bệnh viện này.', $workflow);
        $this->assertStringContainsString('assignManagers($award, $selectedIds, $partnerId, $userId, $actorId)', $workflow);
        $this->assertStringContainsString('public function assignManagers', $service);
        $this->assertStringContainsString('Chọn User → Bệnh viện → các sản phẩm đã phân bổ nhưng chưa có User.', $view);
        $this->assertStringContainsString('Chọn bệnh viện', $view);
        $this->assertStringContainsString('Đã phân công hết', $view);
        $this->assertStringContainsString('sản phẩm đã có User', $view);
        $this->assertStringContainsString('Sản phẩm chưa có User', $view);
        $this->assertStringContainsString('data-multiple-manager', $view);
        $this->assertStringContainsString('data-management-hospital-search', $view);
        $this->assertStringContainsString('data-selected-manager', $view);
        $this->assertStringContainsString("url.searchParams.set('manager_id',userId)", $view);
        $this->assertStringContainsString("@selected((int)(\$selectedManagerId ?? 0) === (int)\$manager->id)", $view);
        $this->assertStringContainsString('name="hospital_id" value="{{ $selectedHospital->id }}"', $view);
        $this->assertStringContainsString('data-multiple-assignment-form', $view);
        $this->assertStringContainsString('Gán User cho sản phẩm đã chọn</button>', $view);
        $this->assertStringNotContainsString('pwa_assigned_hospital_count', $view);
    }

    public function test_multi_user_wizard_live_searches_users_collapses_hospitals_and_returns_after_save(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow=file_get_contents(base_path('Modules/Pharma/Services/ClientBidAwardWorkflow.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-manager-assignment.blade.php'));

        $this->assertStringContainsString('data-manager-results', $view);
        $this->assertStringContainsString('data-manager-result', $view);
        $this->assertStringContainsString('data-manager-empty', $view);
        $this->assertStringContainsString("q?.addEventListener('input',renderUsers)", $view);
        $this->assertStringContainsString("select.dispatchEvent(new Event('change',{bubbles:true}))", $view);
        $this->assertStringContainsString('Không tìm thấy User phù hợp.', $view);
        $this->assertStringContainsString("hospitalCards->count() }} bệnh viện được phân bổ", $view);
        $this->assertStringContainsString("hospitalCards->where('pwa_management_complete',true)->count()", $view);
        $this->assertStringContainsString("hospitalCards->where('pwa_management_complete',false)->count()", $view);
        $this->assertStringContainsString('data-selected-hospital-summary', $view);
        $this->assertStringContainsString('Đổi bệnh viện', $view);
        $this->assertStringContainsString('@if($selectedHospital && ! $selectedHospital->pwa_management_complete)', $view);
        $this->assertStringContainsString('@else', $view);
        $this->assertStringContainsString('data-hospital-picker', $view);
        $this->assertStringContainsString('data-selected-product-count', $view);
        $this->assertStringContainsString("boxes.filter(x=>x.checked).length", $view);
        $this->assertStringContainsString("'manager_id'=>(int) \$data['user_id']", $controller);
        $this->assertStringContainsString("route('client.pharma.bid-awards.manager-assignment', [", $controller);
        $this->assertStringContainsString("sortBy(fn (\$hospital) => (\$hospital->pwa_management_complete ? '1' : '0')", $workflow);
    }

}
