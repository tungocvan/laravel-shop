<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Http\Controllers\AttendanceDashboardController;
use Modules\Attendance\Http\Controllers\AttendanceRecordsController;
use Modules\Attendance\Livewire\AdminRecordsTable;
use Modules\Attendance\Services\AttendanceDashboardService;
use Tests\TestCase;

class AttendanceAdminUiContractTest extends TestCase
{
    public function test_admin_routes_and_permissions_follow_the_approved_contract(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

        $this->assertStringContainsString("prefix('admin/attendance')", $source);
        $this->assertStringContainsString("name('admin.attendance.')", $source);
        $this->assertStringContainsString("'/dashboard'", $source);
        $this->assertStringContainsString("'/records'", $source);
        $this->assertStringContainsString('permission:attendance.dashboard.view,admin', $source);
        $this->assertStringContainsString('permission:attendance.record.view,admin', $source);
    }

    public function test_admin_ui_classes_autoload(): void
    {
        $this->assertTrue(class_exists(AttendanceDashboardController::class));
        $this->assertTrue(class_exists(AttendanceRecordsController::class));
        $this->assertTrue(class_exists(AttendanceDashboardService::class));
        $this->assertTrue(class_exists(AdminRecordsTable::class));
    }

    public function test_records_workspace_uses_bounded_pagination_and_filter_reset(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Livewire/AdminRecordsTable.php'));

        $this->assertStringContainsString('public array $perPageOptions = [10, 25, 50, 100];', $source);
        $this->assertStringContainsString('normalizePerPage', $source);
        $this->assertStringContainsString('resetFilters', $source);
        $this->assertStringContainsString('$this->resetPage()', $source);
        $this->assertStringNotContainsString("'all' =>", $source);
    }

    public function test_records_workspace_exposes_permission_guarded_domain_actions(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Livewire/AdminRecordsTable.php'));

        $this->assertStringContainsString("authorizePermission('attendance.record.adjust')", $source);
        $this->assertStringContainsString("authorizePermission('attendance.record.void')", $source);
        $this->assertStringContainsString("authorizePermission('attendance.adjustment.view')", $source);
        $this->assertStringContainsString("authorizePermission('attendance.adjustment.approve')", $source);
        $this->assertStringContainsString('$this->maintenance->correctTimes(', $source);
        $this->assertStringContainsString('$this->maintenance->void(', $source);
        $this->assertStringContainsString('$this->adjustments->approve(', $source);
        $this->assertStringContainsString('$this->adjustments->reject(', $source);
    }

    public function test_admin_views_follow_form_and_pagination_visual_contract(): void
    {
        $records = file_get_contents(base_path('Modules/Attendance/resources/views/livewire/admin-records-table.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Attendance/resources/views/vendor/pagination/admin-attendance.blade.php'));

        $this->assertStringContainsString('border border-gray-300 bg-white', $records);
        $this->assertStringContainsString("links('Attendance::vendor.pagination.admin-attendance')", $records);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringContainsString('bg-white', $pagination);
        $this->assertStringContainsString('previousPage', $pagination);
        $this->assertStringContainsString('nextPage', $pagination);
        $this->assertStringContainsString('gotoPage', $pagination);
    }
}
