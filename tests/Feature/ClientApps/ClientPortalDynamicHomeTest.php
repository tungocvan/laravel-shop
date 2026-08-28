<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Illuminate\Support\Collection;
use Mockery;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\ClientPortal\Services\PortalContextResolver;
use Tests\TestCase;

class ClientPortalDynamicHomeTest extends TestCase
{
    public function test_zero_application_state_renders_no_access_work_home(): void
    {
        $user = new User();
        $user->id = 3100;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('Chưa có ứng dụng được cấp');
    }

    public function test_single_application_state_redirects_directly_to_manifest_route(): void
    {
        $user = new User();
        $user->id = 3101;

        $application = [
            'key' => 'sample',
            'module' => 'Sample',
            'name' => 'Sample application',
            'description' => 'Sample description',
            'route' => 'client.apps.index',
        ];

        $resolver = Mockery::mock(PortalContextResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn([
            'user_id' => 3101,
            'applications' => collect([$application]),
            'application_count' => 1,
            'single_application' => $application,
            'requires_application_selection' => false,
            'has_access' => true,
        ]);
        $this->app->instance(PortalContextResolver::class, $resolver);

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertRedirect(route('client.apps.index'));
    }

    public function test_multiple_application_state_renders_permission_filtered_work_home_without_redirect(): void
    {
        $user = new User();
        $user->id = 3102;

        $applications = collect([
            [
                'key' => 'alpha',
                'module' => 'Alpha',
                'name' => 'Alpha App',
                'description' => 'Alpha description',
                'route' => 'client.apps.index',
            ],
            [
                'key' => 'beta',
                'module' => 'Beta',
                'name' => 'Beta App',
                'description' => 'Beta description',
                'route' => 'client.apps.index',
            ],
        ]);

        $resolver = Mockery::mock(PortalContextResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn([
            'user_id' => 3102,
            'applications' => $applications,
            'application_count' => 2,
            'single_application' => null,
            'requires_application_selection' => true,
            'has_access' => true,
        ]);
        $this->app->instance(PortalContextResolver::class, $resolver);

        $settings = Mockery::mock(ClientPortalSettingsService::class);
        $settings->shouldReceive('presentApplications')->once()->with(Mockery::type(Collection::class))->andReturn($applications);
        $settings->shouldReceive('pwaGeneral')->once()->andReturn([
            'theme_color' => '#0f172a',
            'application_name' => 'Portal',
            'apple_title' => 'Portal',
            'short_name' => 'Portal',
        ]);
        $settings->shouldReceive('pwaLauncher')->once()->andReturn([
            'browser_title' => 'Portal',
            'brand_title' => 'Portal',
            'brand_subtitle' => 'Workspace',
            'install_button_text' => 'Cài đặt',
            'logout_button_text' => 'Đăng xuất',
            'workspace_label' => 'Không gian làm việc',
            'heading' => 'Ứng dụng của tôi',
            'description' => 'Chọn ứng dụng để tiếp tục.',
            'empty_title' => 'Chưa có ứng dụng được cấp',
            'empty_description' => 'Liên hệ quản trị viên.',
            'show_source_module' => false,
            'open_application_text' => 'Mở ứng dụng',
        ]);
        $this->app->instance(ClientPortalSettingsService::class, $settings);

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('Alpha App')
            ->assertSee('Beta App')
            ->assertSee('2 ứng dụng');
    }

    public function test_dynamic_home_core_does_not_branch_on_named_business_applications_or_roles(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/PortalController.php'));
        $home = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/apps.blade.php'));
        $source = $controller."\n".$home;

        $this->assertStringNotContainsString("'muasamcong'", strtolower($source));
        $this->assertStringNotContainsString('"muasamcong"', strtolower($source));
        $this->assertStringNotContainsString("'request'", strtolower($source));
        $this->assertStringNotContainsString('"request"', strtolower($source));
        $this->assertStringNotContainsString('hasRole(', $source);
        $this->assertStringNotContainsString('hasAnyRole(', $source);
    }
}
