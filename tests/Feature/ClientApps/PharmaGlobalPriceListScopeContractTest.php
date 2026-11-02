<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaGlobalPriceListScopeContractTest extends TestCase
{
    public function test_global_price_list_scope_is_explicit_and_server_permission_is_enforced(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/ApproverGlobalPriceListWorkflow.php'));

        $this->assertStringContainsString('Áp dụng cho tất cả khách hàng', $view);
        $this->assertStringContainsString('<input type="hidden" name="type" value="global">', $view);
        $this->assertStringContainsString('name="apply_all_users"', $view);
        $this->assertStringContainsString('name="global_user_ids[]"', $view);
        $this->assertStringContainsString("client.pharma.price-lists.approve'), 403", $controller);
        $this->assertStringContainsString('$globalWorkflow->createAndActivate(', $controller);
        $this->assertStringContainsString("if (! \$applyAllUsers && \$userIds === [])", $workflow);
        $this->assertStringContainsString("'is_active', true", $workflow);
        $this->assertStringContainsString('$list->globalUsers()->sync($applyAllUsers ? [] : $userIds)', $workflow);
        $this->assertStringContainsString("'source_price_list_id' => 'Bảng giá gốc phải là bảng giá chung đang ACTIVE.'", $workflow);
    }
}
