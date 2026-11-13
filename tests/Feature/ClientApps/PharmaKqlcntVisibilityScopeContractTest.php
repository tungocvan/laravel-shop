<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaKqlcntVisibilityScopeContractTest extends TestCase
{
    #[Test]
    public function permission_is_independently_grantable_and_not_implicit_in_view(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $this->assertStringContainsString("'permission' => 'client.pharma.kqlcnt-awards.view-all'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.kqlcnt-awards.view'", $manifest);
    }

    #[Test]
    public function assigned_user_ids_are_canonical_and_not_compared_by_name(): void
    {
        $service = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntAssignedUserOverview.php'));
        $this->assertStringContainsString('managementAssignmentSummary($award)', $service);
        $this->assertStringContainsString("'user_id' => (int) \$row->user_id", $service);
        $visibility = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntAwardVisibility.php'));
        $this->assertStringContainsString("\$entry['user_id'] === \$id", $visibility);
        $this->assertStringContainsString('abort_unless($allowed, 403)', $visibility);
        $this->assertStringContainsString('array_diff($exportGroups, $visible) === []', $visibility);
    }

    #[Test]
    public function list_dashboard_and_filter_options_are_scoped_before_rendering(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertStringContainsString("if (! \$canViewAll)", $controller);
        $this->assertStringContainsString("filters['assigned_group_ids']", $controller);
        $this->assertStringContainsString('dashboardForGroupIds(array_keys($visibleGroups))', $controller);
        $this->assertStringContainsString('filterOptionsForGroupIds(array_keys($visibleGroups))', $controller);
    }

    #[Test]
    public function item_routes_and_excel_export_enforce_server_side_scope(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertSame(13, substr_count($routes, 'AuthorizeKqlcntAwardItem::class'));
        $excel = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardExcelController.php'));
        $this->assertStringContainsString("authorizeExport(\$request, \$data['notify_no'], \$data['contractor_code'])", $excel);
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php'));
        $this->assertStringContainsString('Phạm vi: TBMT tôi phụ trách', $blade);
    }
}
