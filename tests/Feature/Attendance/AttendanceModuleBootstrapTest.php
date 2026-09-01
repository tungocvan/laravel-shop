<?php

namespace Tests\Feature\Attendance;

use App\Modules\ModuleCatalog;
use App\Modules\ModuleGraphValidator;
use LogicException;
use Tests\TestCase;

class AttendanceModuleBootstrapTest extends TestCase
{
    public function test_attendance_module_manifest_declares_release_one_contract(): void
    {
        $config = require base_path('Modules/Attendance/config/module.php');

        $this->assertSame('Attendance', $config['name']);
        $this->assertSame('domain', $config['type']);
        $this->assertFalse($config['default_enabled']);
        $this->assertSame(['Account'], $config['depends']);
        $this->assertTrue($config['permissions_required']);
        $this->assertSame([
            'attendance_locations',
            'attendance_shifts',
            'attendance_records',
            'attendance_adjustment_requests',
            'attendance_audit_events',
        ], $config['tables']);
    }

    public function test_attendance_permissions_are_explicitly_split_by_guard(): void
    {
        $config = require base_path('Modules/Attendance/config/module.php');

        $this->assertContains('attendance.dashboard.view', $config['permissions']);
        $this->assertContains('attendance.record.adjust', $config['permissions']);
        $this->assertContains('attendance.location.manage', $config['permissions']);
        $this->assertContains('attendance.audit.view', $config['permissions']);

        $this->assertSame([
            'client.attendance.access',
            'attendance.record.view-own',
            'attendance.check-in',
            'attendance.check-out',
            'attendance.adjustment.create',
        ], $config['permissions_by_guard']['web']);
    }

    public function test_catalog_discovers_attendance_without_an_attendance_specific_provider(): void
    {
        $attendance = collect(app(ModuleCatalog::class)->discover())
            ->firstWhere('name', 'Attendance');

        $this->assertNotNull($attendance);
        $this->assertSame('Attendance', $attendance['name']);
        $this->assertSame(['Account'], $attendance['depends']);
        $this->assertFalse($attendance['default_enabled']);
        $this->assertFileDoesNotExist(base_path('Modules/Attendance/Providers/AttendanceServiceProvider.php'));
    }

    public function test_root_module_provider_keeps_convention_based_attendance_bootstrap_available(): void
    {
        $source = file_get_contents(base_path('Modules/ModuleServiceProvider.php'));

        $this->assertStringContainsString('registerConfig($module)', $source);
        $this->assertStringContainsString('registerRoutes($module)', $source);
        $this->assertStringContainsString('registerResources($module)', $source);
        $this->assertStringContainsString('registerMigrations($module)', $source);
        $this->assertStringContainsString('registerLivewireComponents($module)', $source);
        $this->assertStringContainsString('registerConsole($module)', $source);
    }

    public function test_graph_validator_rejects_attendance_when_account_is_disabled(): void
    {
        $catalog = collect(app(ModuleCatalog::class)->discover());
        $account = $catalog->firstWhere('name', 'Account');
        $attendance = $catalog->firstWhere('name', 'Attendance');

        $this->assertNotNull($account);
        $this->assertNotNull($attendance);

        $account['enabled'] = false;
        $attendance['enabled'] = true;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Module [Attendance] requires disabled module [Account].');

        (new ModuleGraphValidator)->validate(collect([$account, $attendance]));
    }

    public function test_release_one_configuration_defaults_are_explicit(): void
    {
        $config = require base_path('Modules/Attendance/config/attendance.php');

        $this->assertSame('08:00', $config['shift']['start_time']);
        $this->assertSame('17:00', $config['shift']['end_time']);
        $this->assertSame(5, $config['shift']['late_grace_minutes']);
        $this->assertSame(5, $config['shift']['early_leave_grace_minutes']);
        $this->assertSame(150, $config['geofence']['default_radius_meters']);
        $this->assertSame(100, $config['geofence']['maximum_accuracy_meters']);
        $this->assertSame(30, $config['privacy']['raw_gps_retention_days']);
        $this->assertTrue($config['geocoding']['enabled']);
    }
}
