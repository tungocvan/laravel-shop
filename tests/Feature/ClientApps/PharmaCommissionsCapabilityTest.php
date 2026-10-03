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

        $show=Route::getRoutes()->getByName('client.pharma.commissions.show');
        $this->assertNotNull($show);
        $this->assertSame('GET',$show->methods()[0]);
        $this->assertSame('apps/pharma/commissions/{issue}',$show->uri());
        $this->assertContains('client.feature:pharma,commissions',$show->gatherMiddleware());
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
        $this->assertStringContainsString("if(\$partnerId!==null)",$service);
        $this->assertStringContainsString("where('partner_id',\$partnerId)",$service);
        $this->assertStringContainsString('public function commissionPartners(',$service);
        $this->assertStringContainsString("SUM(revenue_amount)",$service);
        $this->assertStringContainsString("SUM(commission_amount)",$service);
        $this->assertStringContainsString("groupBy('issue_id')",$service);
        $this->assertStringContainsString("public function detail(",$service);
        $this->assertStringContainsString("->where('issue_id',\$issueId)",$service);

        $reflection=new ReflectionClass(UserCommissionWorkspace::class);
        $this->assertTrue($reflection->hasMethod('browse'));
        $this->assertTrue($reflection->hasMethod('summary'));
    }

    public function test_client_surface_uses_authenticated_user_scope_managed_copy_and_native_load_more(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $manifest=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commissions.blade.php'));
        $detailView=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commission-show.blade.php'));
        $pwaSelectSearch=file_get_contents(base_path('resources/views/components/pwa-select-search.blade.php'));
        $nativeInteractions=file_get_contents(base_path('resources/js/clientportal/native-interactions.js'));

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
        $this->assertStringContainsString("'partner_id'=>['nullable','integer','min:1']",$controller);
        $this->assertStringContainsString('$workspace->commissionPartners((int)$user->id',$controller);
        $this->assertStringContainsString("\$canViewTeam ? \$workspace->commissionUsers() : collect()",$controller);

        $this->assertStringContainsString("@section('hide-application-header', true)",$view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)",$view);
        $this->assertStringContainsString("\$featurePresentation['eyebrow']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_title']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_description']",$view);
        $this->assertStringContainsString('Doanh số ghi nhận',$view);
        $this->assertStringContainsString('Ngày xuất',$view);
        $this->assertStringContainsString('Khách hàng',$view);
        $this->assertStringContainsString('Tổng giá trị',$view);
        $this->assertStringContainsString('Tổng hoa hồng',$view);
        $this->assertStringContainsString("route('client.pharma.commissions.show'",$view);
        $this->assertStringContainsString('Chi tiết phiếu xuất',$detailView);
        $this->assertStringContainsString('SL thực xuất',$detailView);
        $this->assertStringContainsString('Chính sách',$detailView);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)",$detailView);
        $this->assertStringContainsString('Hoa hồng ròng',$view);
        $this->assertStringContainsString('Chưa xác định',$view);
        $this->assertStringContainsString('Xóa bộ lọc',$view);
        $this->assertStringContainsString('$hasCommissionFilters',$view);
        $this->assertStringContainsString('md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]',$view);
        $this->assertStringContainsString('>Áp dụng</button>',$view);
        $this->assertStringContainsString('id="commission-partner"',$view);
        $this->assertStringContainsString('search-placeholder="Tìm khách hàng..."',$view);
        $this->assertStringContainsString('id="commission-mobile-list"',$view);
        $this->assertStringContainsString('hidden overflow-hidden rounded-3xl',$view);
        $this->assertStringContainsString('table-fixed',$view);
        $this->assertStringContainsString('Người phụ trách',$view);
        $this->assertStringContainsString('<x-pwa-select-search',$view);
        $this->assertStringContainsString('search-placeholder="Tìm tên hoặc email..."',$view);
        $this->assertStringContainsString('data-pwa-select-search-option',$view);
        $this->assertStringContainsString('data-pwa-select-search-input',$pwaSelectSearch);
        $this->assertStringContainsString('data-pwa-select-search-panel',$pwaSelectSearch);
        $this->assertStringContainsString("\$attributes->merge(['class' => 'relative min-w-0'])",$pwaSelectSearch);
        $this->assertStringContainsString("@push('application-scripts')",$pwaSelectSearch);
        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded'",$pwaSelectSearch);
        $this->assertStringContainsString('const bindPwaSelectSearch',$nativeInteractions);
        $this->assertStringContainsString("toLocaleLowerCase('vi')",$nativeInteractions);
        $this->assertStringContainsString('@if($canViewTeam)',$view);
        $this->assertStringContainsString('type="date" name="from"',$view);
        $this->assertStringContainsString('type="date" name="to"',$view);
        $this->assertStringContainsString('data-commission-date-label="from"',$view);
        $this->assertStringContainsString('data-commission-date-picker="from"',$view);
        $this->assertStringNotContainsString('input.form.requestSubmit();',$view);
        $this->assertStringContainsString("format('d/m/Y')",$view);
        $this->assertStringContainsString('rounded-2xl border border-slate-200 bg-white',$pwaSelectSearch);
        $this->assertStringNotContainsString('id="commission-search-input"',$view);
        $this->assertStringNotContainsString('data-pwa-debounced-search="800"',$view);
        $this->assertStringContainsString('data-commission-item',$view);
        $this->assertStringContainsString('active:scale-[0.985]',$view);
        $this->assertStringContainsString('motion-reduce:transform-none',$view);
        $this->assertStringNotContainsString('Admin::',$view);
        $this->assertStringNotContainsString('method="POST"',$view);
        $this->assertStringNotContainsString('wire:',$view);
    }
}
