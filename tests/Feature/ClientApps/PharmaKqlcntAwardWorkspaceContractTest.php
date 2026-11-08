<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaKqlcntAwardWorkspaceContractTest extends TestCase
{
    #[Test]
    public function client_routes_are_web_guarded_and_feature_protected(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertStringContainsString("auth:web", $routes);
        $this->assertStringContainsString("client.feature:pharma,kqlcnt-awards", $routes);
        $this->assertStringContainsString("->name('kqlcnt-awards.index')", $routes);
        $this->assertStringContainsString("->name('kqlcnt-awards.show')", $routes);
    }

    #[Test]
    public function manifest_exposes_managed_feature_and_independent_permissions(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $this->assertSame('client.pharma.kqlcnt-awards.view', $manifest['features']['kqlcnt-awards']['permission']);
        $this->assertSame('client.pharma.kqlcnt-awards.view', $manifest['navigation']['kqlcnt-awards']['permission']);
        $this->assertNotEmpty($manifest['features']['kqlcnt-awards']['page_title']);
        $this->assertNotEmpty($manifest['features']['kqlcnt-awards']['page_description']);
        $this->assertNotEmpty($manifest['features']['kqlcnt-awards']['eyebrow']);
        foreach (['export', 'import', 'edit'] as $action) {
            $this->assertSame('client.pharma.kqlcnt-awards.'.$action, $manifest['navigation']['kqlcnt-awards']['actions'][$action]['permission']);
        }
    }

    #[Test]
    public function normalized_feature_is_resolved_by_key_not_associative_index(): void
    {
        $registry = app(\Modules\ClientPortal\Services\ApplicationRegistry::class);
        $application = $registry->find('pharma');
        $this->assertNotNull($application);
        $feature = collect($application['features'])->firstWhere('key', 'kqlcnt-awards');
        $this->assertIsArray($feature);
        $this->assertSame('kqlcnt-awards', $feature['key']);
        $presentation = app(\Modules\ClientPortal\Services\ClientPortalSettingsService::class)
            ->featurePresentation('pharma', $feature);
        $this->assertNotEmpty($presentation['page_title']);

        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertSame(2, substr_count($controller, "firstWhere('key', 'kqlcnt-awards')"));
        $this->assertStringNotContainsString("['features']['kqlcnt-awards']", $controller);
    }

    #[Test]
    public function workspace_groups_by_tbmt_and_contractor_and_exposes_medicine_search(): void
    {
        $service = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardReadService.php'));
        $this->assertStringContainsString("groupBy('notify_no', 'contractor_code', 'contractor_name')", $service);
        $this->assertStringContainsString("orWhere('active_ingredient'", $service);
        $this->assertStringContainsString('whereExists(', $service);
        $this->assertStringContainsString('SUM(COALESCE(amount, quantity * winning_price, 0))', $service);
        $this->assertStringContainsString('havingRaw(', $service);
        $this->assertStringContainsString('public function dashboard()', $service);
        $this->assertStringContainsString('public function filterOptions()', $service);

        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php'));
        $this->assertStringContainsString('<x-pwa-search', $list);
        $this->assertStringContainsString('name="notify_no"', $list);
        $this->assertStringContainsString('name="investor_code"', $list);
        $this->assertStringContainsString('Tổng giá trị trúng thầu', $list);
        $this->assertStringContainsString('Giá trị đang hoạt động', $list);
        $this->assertStringNotContainsString('@filled(', $list);
        $this->assertStringContainsString('Chủ đầu tư', $list);
        $this->assertStringContainsString('Thời gian thực hiện hợp đồng', $list);
        $this->assertStringContainsString('Số tiền trúng thầu', $list);
        $this->assertStringContainsString('Số lượng sản phẩm', $list);

        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-award-detail.blade.php'));
        $this->assertStringContainsString('@foreach($medicines as $medicine)', $detail);
        $this->assertStringContainsString('Danh mục thuốc trúng thầu', $detail);
    }

    #[Test]
    public function client_workspace_does_not_call_admin_controller_or_modify_canonical_records(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertStringContainsString('KqlcntAwardReadService $awards', $source);
        $domain = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardReadService.php'));
        $this->assertStringContainsString('KqlcntAwardItem::query()', $domain);
        $this->assertStringNotContainsString('use Modules\\Muasamcong\\Http\\Controllers\\KqlcntAwardController;', $source);
        $this->assertStringNotContainsString('KqlcntAwardController::class', $source);
        $this->assertStringNotContainsString('->save()', $source);
        $this->assertStringNotContainsString('->update(', $source);
        $this->assertStringContainsString('featurePresentation(', $source);
    }
}
