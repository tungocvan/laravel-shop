<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;
use Modules\ClientPortal\Http\Controllers\Admin\ApplicationAdminController;
use Modules\ClientPortal\Http\Controllers\PortalController;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Support\ApplicationContext;
use Tests\TestCase;

class ClientPortalExtractionTest extends TestCase
{
    public function test_client_portal_is_a_separate_optional_support_module(): void
    {
        $module = config('modules.registry.ClientPortal');

        $this->assertIsArray($module);
        $this->assertSame('support', $module['type']);
        $this->assertTrue((bool) $module['enabled']);
        $this->assertContains('Auth', $module['depends']);
        $this->assertSame(app(ApplicationContext::class), app(ApplicationContext::class));
    }

    public function test_portal_routes_are_owned_by_client_portal_controllers(): void
    {
        $this->assertSame(PortalController::class.'@index', Route::getRoutes()->getByName('client.apps.index')?->getActionName());
        $this->assertSame(ApplicationAdminController::class.'@index', Route::getRoutes()->getByName('admin.client-apps.index')?->getActionName());
        $this->assertSame(MuasamcongApplicationController::class.'@dashboard', Route::getRoutes()->getByName('client.muasamcong.dashboard')?->getActionName());
        $this->assertSame(MuasamcongApplicationController::class.'@drugPricing', Route::getRoutes()->getByName('client.muasamcong.drug-pricing')?->getActionName());
    }

    public function test_disabling_source_module_hides_only_its_client_adapter(): void
    {
        $this->assertNotNull(app(ApplicationRegistry::class)->find('muasamcong'));

        config(['modules.registry.Muasamcong.enabled' => false]);

        $this->assertNull(app(ApplicationRegistry::class)->find('muasamcong'));
        $this->assertNotNull(Route::getRoutes()->getByName('muasamcong.index'));
    }
}
