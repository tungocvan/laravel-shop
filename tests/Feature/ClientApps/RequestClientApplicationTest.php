<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RequestClientApplicationTest extends TestCase
{
    public function test_registry_discovers_request_adapter_manifest_when_request_module_is_enabled(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $application = app(ApplicationRegistry::class)->find('request');

        $this->assertNotNull($application);
        $this->assertSame('Request', $application['module']);
        $this->assertSame('Đề nghị & Phê duyệt', $application['name']);
        $this->assertSame('client.request.dashboard', $application['route']);
        $this->assertSame('client.request.access', $application['permission']);
        $this->assertSame(
            ['overview', 'create', 'mine', 'inbox', 'processed'],
            collect($application['features'])->pluck('key')->all(),
        );
    }

    public function test_request_application_disappears_when_source_module_is_disabled(): void
    {
        config()->set('modules.registry.Request.enabled', false);

        $this->assertNull(app(ApplicationRegistry::class)->find('request'));
    }

    public function test_guest_is_redirected_from_request_dashboard(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $this->get('/apps/request')->assertRedirect(route('client.apps.login'));
    }

    public function test_web_user_with_request_client_permissions_can_open_dashboard(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $user = User::factory()->create();
        foreach (['client.request.access', 'client.request.overview.view'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user->givePermissionTo('client.request.access', 'client.request.overview.view');

        $this->actingAs($user, 'web')
            ->get('/apps/request')
            ->assertOk()
            ->assertSee('Đề nghị &amp; Phê duyệt', false)
            ->assertSee('Cần phê duyệt');
    }
}
