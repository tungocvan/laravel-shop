<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaOrderManagerSourceContractTest extends TestCase
{
    public function test_order_form_restores_bid_source_from_manager_navigation_query(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));

        $this->assertStringContainsString("request()->query('source')", $view);
        $this->assertStringContainsString("in_array(\$requestedSource, ['bid', 'price_list'], true)", $view);
        $this->assertStringContainsString("u.searchParams.set('manager_user_id',m.id)", $view);
        $this->assertStringContainsString("u.searchParams.set('source',document.querySelector", $view);
        $this->assertStringContainsString("old('source',", $view);
        $this->assertStringContainsString("syncSource();", $view);
    }
}
