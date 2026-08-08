<?php

namespace Tests\Feature\Admission;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class AdmissionRouteConfigurationTest extends TestCase
{
    public function test_admission_module_is_enabled(): void
    {
        $this->assertTrue((bool) config('admission.module.enabled'));
    }

    public function test_admission_dashboard_does_not_override_admin_dashboard_route(): void
    {
        $adminDashboard = Route::getRoutes()->getByName('admin.dashboard');
        $admissionDashboard = Route::getRoutes()->getByName('admin.admission.dashboard');

        $this->assertNotNull($adminDashboard);
        $this->assertSame('admin', $adminDashboard->uri());

        $this->assertNotNull($admissionDashboard);
        $this->assertSame('admin/admission/dashboard', $admissionDashboard->uri());
    }

    public function test_admission_api_stub_returns_intentional_response(): void
    {
        $this->getJson('/api/admission')
            ->assertStatus(501)
            ->assertJson([
                'message' => 'Admission API is not available yet.',
            ]);
    }

    public function test_permission_seeder_reads_admission_lowercase_module_config(): void
    {
        $method = new ReflectionMethod(RolesAndPermissionsSeeder::class, 'loadModulePermissions');
        $method->setAccessible(true);

        $permissions = $method->invoke(new RolesAndPermissionsSeeder());

        $this->assertContains('view_admission', $permissions);
        $this->assertContains('manage_admission_locations', $permissions);
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_admin_admission_routes_enforce_named_permissions(string $routeName, string $uri, string $permission): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route);
        $this->assertSame($uri, $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains("permission:{$permission},admin", $route->gatherMiddleware());
    }

    public static function adminRoutesProvider(): array
    {
        return [
            ['admin.admission.dashboard', 'admin/admission/dashboard', 'view_admission'],
            ['admin.admission.index', 'admin/admission', 'view_admission'],
            ['admin.admission.create', 'admin/admission/create', 'create_admission'],
            ['admin.admission.edit', 'admin/admission/edit/{id}', 'edit_admission'],
            ['admin.admission.export-pdf', 'admin/admission/export-pdf/{id}', 'download_admission_documents'],
            ['admin.admission.export', 'admin/admission/export', 'export_admission'],
            ['admin.admission.import', 'admin/admission/import', 'import_admission'],
            ['admin.admission.dvhc', 'admin/admission/dvhc', 'manage_admission_locations'],
            ['admin.admission.list-class', 'admin/admission/list-class', 'view_admission'],
            ['admission.register', 'admission/register', 'create_admission'],
            ['admission.download-pdf', 'admission/download-pdf/{id}', 'download_admission_documents'],
            ['admission.download-word', 'admission/download-word/{id}', 'download_admission_documents'],
            ['admission.download', 'admission/{id}/download/{type}', 'download_admission_documents'],
            ['admission.receipt', 'admission/{id}/receipt', 'download_admission_documents'],
        ];
    }
}
