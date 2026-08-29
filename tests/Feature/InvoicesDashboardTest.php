<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Models\InvoiceBackupRun;
use Modules\Invoices\Models\InvoiceFile;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\InvoiceDashboardService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoicesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_uses_the_expected_contract_and_preserves_index_redirect(): void
    {
        $route = Route::getRoutes()->getByName('admin.invoices.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('admin/invoices/dashboard', $route->uri());
        $this->assertContains('auth:admin', $route->getAction('middleware'));
        $this->assertContains('permission:invoices-list', $route->getAction('middleware'));

        $this->get(route('admin.invoices.dashboard'))->assertRedirect();

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.invoices.dashboard'))
            ->assertForbidden();

        $viewer = $this->adminWithPermissions(['invoices-list']);
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.invoices.index'))
            ->assertRedirect(route('admin.invoices.hoadon-list'));

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'admin'));

        $this->actingAs($superAdmin->fresh(), 'admin')
            ->get(route('admin.invoices.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_renders_capability_aware_navigation_without_sensitive_data_or_remote_calls(): void
    {
        Http::preventStrayRequests();

        $invoice = $this->createInvoice(1, 'sold');
        InvoiceFile::query()->create([
            'invoice_id' => $invoice->id,
            'provider' => 'local',
            'status' => 'error',
            'path' => 'private/secret-invoice-path.pdf',
            'last_error' => 'raw-pdf-error-secret',
        ]);
        InvoiceBackupRun::query()->create([
            'mode' => 'automatic',
            'status' => 'failed',
            'recipient' => 'backup-secret@example.test',
            'files' => [['name' => 'secret-file.pdf', 'fingerprint' => 'secret-fingerprint']],
            'message' => 'raw-backup-error-secret',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        config([
            'invoices.gdt.username' => 'gdt-user-secret',
            'invoices.gdt.password' => 'gdt-password-secret',
            'invoices.gdt.cache_key' => 'dashboard-gdt-token',
        ]);
        Cache::put('dashboard-gdt-token', 'gdt-token-secret', 600);

        $viewer = $this->adminWithPermissions(['invoices-list']);
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.invoices.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard hóa đơn')
            ->assertSee('Danh sách hóa đơn')
            ->assertSee('Tổng hợp đối tác')
            ->assertDontSee('href="'.route('admin.invoices.hoadon').'"', false)
            ->assertDontSee('href="'.route('admin.invoices.create-token').'"', false)
            ->assertDontSee('PDF khả dụng')
            ->assertDontSee('Backup gần đây');

        $operator = $this->adminWithPermissions([
            'invoices-list',
            'invoices-create',
            'invoices-export',
            'invoices-download',
            'invoices-configure',
        ]);

        $response = $this->actingAs($operator, 'admin')
            ->get(route('admin.invoices.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.invoices.hoadon').'"', false)
            ->assertSee('href="'.route('admin.invoices.create-token').'"', false)
            ->assertSee('PDF khả dụng')
            ->assertSee('Backup gần đây')
            ->assertSee('Đã cấu hình tài khoản')
            ->assertSee('Phiên server: Đang khả dụng');

        foreach ([
            'lookup-secret-1',
            'INV-SECRET-1',
            'Sensitive Partner 1',
            'tax-secret-1',
            'sensitive-1@example.test',
            'private/secret-invoice-path.pdf',
            'raw-pdf-error-secret',
            'backup-secret@example.test',
            'secret-file.pdf',
            'secret-fingerprint',
            'raw-backup-error-secret',
            'gdt-user-secret',
            'gdt-password-secret',
            'gdt-token-secret',
            '12345.67',
        ] as $secret) {
            $response->assertDontSee($secret, false);
        }
    }

    public function test_dashboard_service_returns_bounded_safe_dto_and_constant_query_count(): void
    {
        $admin = $this->adminWithPermissions([
            'invoices-list',
            'invoices-download',
            'invoices-configure',
        ]);

        foreach (range(1, 7) as $index) {
            $invoice = $this->createInvoice($index, $index % 2 === 0 ? 'purchase' : 'sold');

            if ($index === 1) {
                InvoiceFile::query()->create([
                    'invoice_id' => $invoice->id,
                    'provider' => 'local',
                    'status' => 'available',
                    'path' => 'private/dashboard-file-secret.pdf',
                ]);
            }

            if ($index === 2) {
                InvoiceFile::query()->create([
                    'invoice_id' => $invoice->id,
                    'provider' => 'gdt',
                    'status' => 'error',
                    'last_error' => 'dashboard-pdf-error-secret',
                ]);
            }

            InvoiceBackupRun::query()->create([
                'mode' => $index % 2 === 0 ? 'manual' : 'automatic',
                'status' => $index === 7 ? 'failed' : 'success',
                'recipient' => "backup-{$index}@example.test",
                'files_count' => $index,
                'emails_sent' => 1,
                'files' => [['name' => "backup-secret-{$index}.pdf"]],
                'message' => "backup-message-secret-{$index}",
                'started_at' => now()->subMinutes(7 - $index),
                'finished_at' => now()->subMinutes(7 - $index),
            ]);
        }

        $service = app(InvoiceDashboardService::class);
        $dashboard = $service->forUser($admin);
        $data = $dashboard->toArray();
        $serialized = json_encode($dashboard, JSON_THROW_ON_ERROR);

        $this->assertSame(7, $data['metrics']['invoices']['total']);
        $this->assertSame(4, $data['metrics']['invoices']['sold']);
        $this->assertSame(3, $data['metrics']['invoices']['purchase']);
        $this->assertSame(1, $data['metrics']['pdf']['stored']);
        $this->assertSame(1, $data['metrics']['pdf']['error']);
        $this->assertSame(5, $data['metrics']['pdf']['missing']);
        $this->assertCount(5, $data['recent_invoices']);
        $this->assertCount(5, $data['recent_backup_runs']);

        foreach ([
            'lookup-secret-',
            'INV-SECRET-',
            'Sensitive Partner',
            'tax-secret-',
            'sensitive-',
            '12345.67',
            'dashboard-file-secret.pdf',
            'dashboard-pdf-error-secret',
            'backup-secret-',
            'backup-message-secret-',
            'lookup_code',
            'invoice_number',
            'tax_code',
            'vat_amount',
            'amount_before_vat',
            'total_amount',
            'recipient',
            'last_error',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }

        // Warm permission relationships before measuring the bounded dashboard queries.
        $service->forUser($admin);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->forUser($admin);
        $baselineQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        foreach (range(8, 47) as $index) {
            $this->createInvoice($index, $index % 2 === 0 ? 'purchase' : 'sold');
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->forUser($admin);
        $expandedQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $baselineQueryCount);
        $this->assertSame($baselineQueryCount, $expandedQueryCount);
    }

    public function test_dashboard_renders_unavailable_and_empty_states_without_querying_missing_tables(): void
    {
        $viewer = $this->adminWithPermissions(['invoices-list', 'invoices-download']);

        Schema::partialMock()
            ->shouldReceive('hasTable')
            ->andReturn(false);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.invoices.dashboard'))
            ->assertOk()
            ->assertSee('nhóm dữ liệu chưa sẵn sàng')
            ->assertSee('Chưa có hoạt động hóa đơn để hiển thị')
            ->assertSee('Dữ liệu PDF chưa sẵn sàng');
    }

    public function test_admin_workspaces_expose_a_permission_aware_dashboard_return_link(): void
    {
        $viewer = $this->adminWithPermissions(['invoices-list']);

        $this->actingAs($viewer, 'admin');
        $rendered = view('Invoices::partials.dashboard-return-link')->render();

        $this->assertStringContainsString(route('admin.invoices.dashboard'), $rendered);
        $this->assertStringContainsString('Quay về Dashboard', $rendered);

        $configManager = $this->adminWithPermissions(['invoices-configure']);

        $this->actingAs($configManager, 'admin');
        $renderedWithoutListPermission = view('Invoices::partials.dashboard-return-link')->render();

        $this->assertStringNotContainsString(route('admin.invoices.dashboard'), $renderedWithoutListPermission);

        foreach ([
            'authenticate.blade.php',
            'sync.blade.php',
            'index.blade.php',
            'partner-report.blade.php',
        ] as $workspaceView) {
            $source = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/'.$workspaceView));

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "@include('Invoices::partials.dashboard-return-link')",
                $source,
                $workspaceView,
            );
        }
    }

    private function adminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'admin'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin->fresh();
    }

    private function createInvoice(int $index, string $type): Invoices
    {
        return Invoices::query()->create([
            'lookup_code' => 'lookup-secret-'.$index,
            'symbol' => '1/C26T',
            'invoice_number' => 'INV-SECRET-'.$index,
            'type' => 'Hóa đơn GTGT',
            'issued_date' => now()->subDays($index)->toDateString(),
            'tax_code' => 'tax-secret-'.$index,
            'name' => 'Sensitive Partner '.$index,
            'address' => 'Sensitive Address '.$index,
            'email' => "sensitive-{$index}@example.test",
            'phone' => '090000000'.$index,
            'tax_rate' => '10',
            'vat_amount' => '1234.56',
            'amount_before_vat' => '11111.11',
            'total_amount' => '12345.67',
            'invoice_type' => $type,
        ]);
    }
}
