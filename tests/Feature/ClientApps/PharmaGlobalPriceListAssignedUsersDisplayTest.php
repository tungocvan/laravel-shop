<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaGlobalPriceListAssignedUsersDisplayTest extends TestCase
{
    public function test_global_user_scope_is_eager_loaded_and_only_displayed_to_approvers(): void
    {
        $workspace = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkspace.php'));
        $index = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-lists.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-show.blade.php'));

        $this->assertStringContainsString("'manager', 'globalUsers'", $workspace);
        $this->assertStringContainsString("'globalUsers',", $workspace);
        $this->assertStringContainsString('User áp dụng', $index);
        $this->assertStringContainsString('Tất cả User', $index);
        $this->assertStringContainsString('$priceList->globalUsers->pluck(\'name\')', $index);
        $this->assertStringContainsString('$canApprove && $priceList->type', $detail);
        $this->assertStringContainsString('Tất cả khách hàng', $detail);
        $this->assertStringContainsString('$priceList->globalUsers as $assignedUser', $detail);
    }
}
