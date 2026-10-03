<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\UserCommissionWorkspace;
use ReflectionClass;
use Tests\TestCase;

final class PharmaCommissionsCapabilityTest extends TestCase
{
    public function test_commissions_route_is_read_only_and_feature_guarded(): void
    {
        if(! (bool)config('modules.registry.Pharma.enabled',false)) $this->markTestSkipped('Pharma source module is disabled.');

        $route=Route::getRoutes()->getByName('client.pharma.commissions');
        $this->assertNotNull($route);
        $this->assertSame('GET',$route->methods()[0]);
        $this->assertSame('apps/pharma/commissions',$route->uri());
        $this->assertContains('auth:web',$route->gatherMiddleware());
        $this->assertContains('client.application:pharma',$route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,commissions',$route->gatherMiddleware());
        $this->assertNotContains('auth:admin',$route->gatherMiddleware());
    }

    public function test_user_workspace_starts_from_canonical_user_scope_and_never_admin_scope(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/UserCommissionWorkspace.php'));

        $this->assertStringContainsString('CommissionQueryService $commissions',$service);
        $this->assertStringContainsString('->userQuery($userId',$service);
        $this->assertStringContainsString('if(!$canViewTeam)',$service);
        $this->assertStringContainsString('return $this->commissions->userQuery($userId,$filters);',$service);
        $this->assertStringContainsString('return $this->commissions->adminQuery($filters);',$service);
        $this->assertStringContainsString("if(\$managerUserId!==null)",$service);
        $this->assertStringContainsString("whereHas('medicine'",$service);
        $this->assertStringContainsString("orWhereHas('partner'",$service);
        $this->assertStringContainsString("orWhereHas('issue'",$service);
        $this->assertStringContainsString("SUM(revenue_amount)",$service);
        $this->assertStringContainsString("SUM(commission_amount)",$service);

        $reflection=new ReflectionClass(UserCommissionWorkspace::class);
        $this->assertTrue($reflection->hasMethod('browse'));
        $this->assertTrue($reflection->hasMethod('summary'));
    }

    public function test_client_surface_uses_authenticated_user_scope_managed_copy_and_native_load_more(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $manifest=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commissions.blade.php'));

        $this->assertStringContainsString('UserCommissionWorkspace $workspace',$controller);
        $this->assertStringContainsString("'client.pharma.commissions.view'",$controller);
        $this->assertStringContainsString('$workspace->browse((int)$user->id',$controller);
        $this->assertStringContainsString('$workspace->summary((int)$user->id',$controller);
        $this->assertStringContainsString("featurePresentation(\$application['key'],\$feature)",$controller);

        $this->assertStringContainsString("'route' => 'client.pharma.commissions'",$manifest);
        $this->assertStringContainsString("'page_title' => 'Hoa hồng của tôi'",$manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.commissions.view'",$manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.commissions.view-team'",$manifest);
        $this->assertStringContainsString("\$registry->userCan(\$user,'client.pharma.commissions.view-team')",$controller);
        $this->assertStringContainsString("'manager_user_id'=>['nullable','integer','min:1']",$controller);
        $this->assertStringContainsString("\$canViewTeam ? \$workspace->commissionUsers() : collect()",$controller);

        $this->assertStringContainsString("@section('hide-application-header', true)",$view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)",$view);
        $this->assertStringContainsString("\$featurePresentation['eyebrow']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_title']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_description']",$view);
        $this->assertStringContainsString('Doanh số ghi nhận',$view);
        $this->assertStringContainsString('Hoa hồng ròng',$view);
        $this->assertStringContainsString('Chưa xác định',$view);
        $this->assertStringContainsString('Xóa bộ lọc',$view);
        $this->assertStringContainsString('Người phụ trách',$view);
        $this->assertStringContainsString('<x-select-search id="commission-manager-user"',$view);
        $this->assertStringContainsString('@if($canViewTeam)',$view);
        $this->assertStringContainsString('min-w-0 overflow-hidden',$view);
        $this->assertStringContainsString('style="width:100%;min-width:0;max-width:100%;"',$view);
        $this->assertStringContainsString('data-pwa-debounced-search="800"',$view);
        $this->assertStringContainsString('data-pwa-search-clear-button="#commission-search-input"',$view);
        $this->assertStringContainsString('data-pwa-load-more',$view);
        $this->assertStringContainsString('data-commission-item',$view);
        $this->assertStringContainsString('active:scale-[0.985]',$view);
        $this->assertStringContainsString('motion-reduce:transform-none',$view);
        $this->assertStringNotContainsString('Admin::',$view);
        $this->assertStringNotContainsString('method="POST"',$view);
        $this->assertStringNotContainsString('wire:',$view);
    }
}
