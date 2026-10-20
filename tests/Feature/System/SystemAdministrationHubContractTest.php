<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemAdministrationHubContractTest extends TestCase
{
    public function test_hub_links_to_existing_canonical_routes_without_moving_ownership(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/SettingController.php'));
        $expected = [
            'admin.system.index' => 'system.manage',
            'admin.system.modules' => 'system.modules.view',
            'admin.system.database.index' => 'database.view',
            'admin.layout' => 'admin.layout.view',
            'admin.system.artisan' => 'system.commands.run',
            'admin.system.scripts' => 'system.commands.run',
        ];

        foreach ($expected as $name => $permission) {
            $this->assertTrue(Route::has($name), "Missing canonical route: {$name}");
            $this->assertStringContainsString("'route' => '{$name}'", $controller);
            $this->assertStringContainsString("'permission' => '{$permission}'", $controller);
        }

        $this->assertContains('permission:admin.layout.view,admin', Route::getRoutes()->getByName('admin.layout')->gatherMiddleware());
        $this->assertContains('permission:system.settings.view,admin', Route::getRoutes()->getByName('admin.system.settings.index')->gatherMiddleware());
    }

    public function test_hub_filters_workspaces_by_admin_permissions_and_uses_responsive_cards(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/SettingController.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/settings/index.blade.php'));

        $this->assertStringContainsString("user?->can(\$item['permission'])", $controller);
        $this->assertStringContainsString("route(\$workspace['route'], \$workspace['params'] ?? [])", $view);
        $this->assertStringContainsString('sm:grid-cols-2 xl:grid-cols-3', $view);
        $this->assertStringContainsString('System Administration Hub', $view);
        $this->assertStringNotContainsString("@livewire('system.settings.setting-form')", $view);
    }
}
