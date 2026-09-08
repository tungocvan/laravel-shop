<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InvoicesBackupRestoreWorkspaceTest extends TestCase
{
    public function test_backup_restore_route_is_registered_with_configure_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.invoices.backup-restore');

        $this->assertNotNull($route);
        $this->assertSame('admin/invoices/backup-restore', $route->uri());
        $this->assertContains('permission:invoices-configure', $route->gatherMiddleware());
    }

    public function test_workspace_exposes_restore_readiness_and_partner_boundary_copy(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/module-backup-restore.blade.php'));

        $this->assertStringContainsString('Kiểm tra khả năng khôi phục', $view);
        $this->assertStringContainsString('Tạo Safety Backup + Khôi phục Merge', $view);
        $this->assertStringContainsString('Partner master', $view);
        $this->assertStringContainsString('0 thay đổi', $view);
    }
}
