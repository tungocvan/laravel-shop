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

    public function test_bid_award_workspace_is_a_user_scoped_pharma_read_contract(): void
    {
        $service = new ReflectionClass(UserBidAwardWorkspace::class);
        foreach (['browseAssignedResults', 'findAssignedResult', 'assignedProducts'] as $method) {
            $this->assertTrue($service->hasMethod($method));
            $this->assertTrue($service->getMethod($method)->isPublic());
        }

        $source = file_get_contents(base_path('Modules/Pharma/Services/UserBidAwardWorkspace.php'));
        $this->assertStringContainsString("->where('workspace_assignments.user_id', \$userId)", $source);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::STATUS_ACTIVE', $source);
        $this->assertStringContainsString('DrugBidAwardAllocation::STATUS_ACTIVE', $source);
        $this->assertStringContainsString("MAX(awards.contract_duration_months) as contract_duration_months", $source);
        $this->assertStringContainsString("MAX(awards.contract_period_text) as contract_period_text", $source);
        $this->assertStringContainsString("'scope_key' => sha1(\$identity)", $source);
        $this->assertStringNotContainsString('auth()', $source);
        $this->assertStringNotContainsString('auth(', $source);
    }

    public function test_client_bid_awards_consumes_managed_presentation_and_mobile_workspace(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-awards.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-show.blade.php'));

        $this->assertStringContainsString("'route' => 'client.pharma.bid-awards'", $manifest);
        $this->assertStringContainsString("'eyebrow' => 'Bid Awards'", $manifest);
        $this->assertStringContainsString("'page_title' => 'Kết quả trúng thầu của tôi'", $manifest);
        $this->assertStringContainsString("'page_description' =>", $manifest);

        $this->assertStringContainsString('UserBidAwardWorkspace $workspace', $controller);
        $this->assertStringContainsString('$workspace->browseAssignedResults(', $controller);
        $this->assertStringContainsString('$workspace->findAssignedResult((int) $user->id, $scope)', $controller);
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
        $this->assertStringContainsString('kết quả trong phạm vi bạn phụ trách', $list);
        $this->assertStringContainsString('SL phân bổ', $list);
        $this->assertStringContainsString('Giá trị phân bổ', $list);
        $this->assertStringContainsString('Còn {{ str_pad', $list);
        $this->assertStringContainsString('md:grid-cols-2 xl:grid-cols-3', $list);
        $this->assertStringContainsString('\\Carbon\\Carbon::parse', $list);
        $this->assertStringNotContainsString('CarbonCarbon::parse', $list);
        $this->assertStringContainsString("setTimeout(()=>form.requestSubmit(),350)", $list);
        $this->assertStringContainsString('Xem thêm sản phẩm', $detail);
        $this->assertStringContainsString('Giá trúng thầu', $detail);
        $this->assertStringContainsString('SL phân bổ', $detail);
        $this->assertStringNotContainsString('{{ $size }} / trang', $list);
    }
}
