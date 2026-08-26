<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestUxPhaseTwoFinalAcceptanceContractTest extends TestCase
{
    public function test_phase_two_workspace_slices_keep_their_acceptance_contracts(): void
    {
        foreach ([
            'RequestWorkspaceNavigationContractTest.php',
            'RequestProductionDashboardContractTest.php',
            'RequestEmployeeWorkspaceContractTest.php',
            'RequestDetailWorkspaceContractTest.php',
            'RequestApproverPendingWorkspaceContractTest.php',
            'RequestApproverHistoryWorkspaceContractTest.php',
            'RequestAdminGroupsWorkspaceContractTest.php',
            'RequestAdminDesignerWorkspaceContractTest.php',
            'RequestDefinitionManagementWorkspaceContractTest.php',
            'RequestVersionHistoryWorkspaceContractTest.php',
            'RequestDefinitionPackageWorkspaceContractTest.php',
            'RequestOperationsWorkspaceContractTest.php',
            'RequestReportsExportWorkspaceContractTest.php',
        ] as $contract) {
            $this->assertFileExists(base_path('tests/Feature/Request/Architecture/'.$contract));
        }
    }

    public function test_phase_two_handoff_copy_and_dashboard_deep_links_are_production_safe(): void
    {
        $dashboard = file_get_contents(base_path('Modules/Request/resources/views/dashboard.blade.php'));
        $dashboardBack = file_get_contents(base_path('Modules/Request/resources/views/partials/dashboard-back.blade.php'));
        $myRequests = file_get_contents(base_path('Modules/Request/Livewire/Requester/MyRequests.php'));

        $this->assertStringContainsString("route('request.mine', ['workspace' => 'processing'])", $dashboard);
        $this->assertStringContainsString("route('request.mine', ['workspace' => 'returned'])", $dashboard);
        $this->assertStringContainsString("#[Url(except: 'all')]", $myRequests);
        $this->assertStringContainsString('Quay về Tổng quan Đề nghị', $dashboardBack);

        foreach (['REQUEST_UI_DEMO', 'Bảng kiểm thử Đề nghị', "route('request.mine', ['status' => 'pending'])"] as $internalCopy) {
            $this->assertStringNotContainsString($internalCopy, $dashboard.$dashboardBack);
        }
    }
}
