<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaGlobalPriceListRevisionContractTest extends TestCase
{
    public function test_revision_workflow_preserves_active_source_until_atomic_activation(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/GlobalPriceListRevisionWorkflow.php'));
        $this->assertStringContainsString('DB::transaction', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('$this->manager->clone($source', $service);
        $this->assertStringContainsString('$revision->globalUsers()->sync(', $service);
        $this->assertStringContainsString('$this->manager->deactivate($source)', $service);
        $this->assertStringContainsString('$this->manager->activate($revision, $approverId)', $service);
        $this->assertStringContainsString('in_array($payload[\'identity_key\'], $existing, true)', $service);
    }

    public function test_revision_routes_are_permission_gated_and_visible_from_detail(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $show = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-show.blade.php'));
        $editor = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-revision.blade.php'));

        foreach (['price-lists.revision.start', 'price-lists.revision.edit', 'price-lists.revision.items.store', 'price-lists.revision.activate'] as $route) {
            $this->assertStringContainsString("->name('{$route}')", $routes);
        }
        $this->assertStringContainsString("client.pharma.price-lists.approve", $controller);
        $this->assertStringContainsString('price-lists.revision.start', $show);
        $this->assertStringContainsString('price-lists.revision.items.store', $editor);
        $this->assertStringContainsString('400', $editor);
        $this->assertStringContainsString('revision-clear', $editor);
    }
}
