<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaCustomerPriceListManagerAssignmentContractTest extends TestCase
{
    public function test_customer_price_list_manager_assignment_is_guarded_and_persisted(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkflow.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));

        $this->assertStringContainsString("client.pharma.price-lists.approve');", $controller);
        $this->assertStringContainsString("activeManagers->contains('id', \$managerId)", $controller);
        $this->assertStringContainsString("abort_unless(\$managerId > 0", $controller);
        $this->assertStringContainsString("\$header['manager_user_id'] = \$managerId", $controller);
        $this->assertStringContainsString("(int) (\$header['manager_user_id'] ?? \$userId)", $workflow);
        $this->assertStringContainsString('User phụ trách *', $view);
        $this->assertStringContainsString('name="manager_user_id"', $view);
        $this->assertStringContainsString('@if($canApprove && !$isEditing)', $view);
    }
}
