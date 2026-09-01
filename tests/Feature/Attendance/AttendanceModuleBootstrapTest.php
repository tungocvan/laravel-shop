<?php

namespace Tests\Feature\Attendance;

use App\Modules\ModuleCatalog;
use App\Modules\ModuleGraphValidator;
use App\Modules\ModuleStateRepository;
use App\Modules\ModuleStateResolver;
use LogicException;
use Mockery;
use Tests\TestCase;

class AttendanceModuleBootstrapTest extends TestCase
{
    public function test_attendance_manifest_matches_the_approved_bootstrap_contract(): void
    {
        $manifest = require base_path('Modules/Attendance/config/module.php');

        $this->assertSame('Attendance', $manifest['name']);
        $this->assertSame('domain', $manifest['type']);
        $this->assertFalse($manifest['enabled']);
        $this->assertFalse($manifest['default_enabled']);
        $this->assertSame(['Account'], $manifest['depends']);
        $this->assertTrue($manifest['permissions_required']);
        $this->assertSame([
            'attendance_locations',
            'attendance_shifts',
            'attendance_records',
            'attendance_adjustment_requests',
            'attendance_audit_events',
        ], $manifest['tables']);
        $this->assertContains('attendance.dashboard.view', $manifest['permissions']);
        $this->assertContains('attendance.audit.view', $manifest['permissions']);
        $this->assertContains('client.attendance.access', $manifest['permissions_by_guard']['web']);
        $this->assertContains('attendance.check-in', $manifest['permissions_by_guard']['web']);
        $this->assertContains('attendance.check-out', $manifest['permissions_by_guard']['web']);
    }

    public function test_attendance_is_discovered_disabled_by_default(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->once()->with('Attendance')->andReturnNull();

        $module = (new ModuleCatalog(new ModuleStateResolver($states)))
            ->resolve(base_path('Modules/Attendance'));

        $this->assertSame('Attendance', $module['name']);
        $this->assertSame('domain', $module['type']);
        $this->assertFalse($module['enabled']);
        $this->assertFalse($module['required']);
        $this->assertSame(['Account'], $module['depends']);
        $this->assertSame('manifest', $module['source']);
        $this->assertTrue($module['manifest_exists']);
        $this->assertFalse($module['default_enabled']);
    }

    public function test_runtime_state_can_enable_attendance_when_account_is_enabled(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->once()->with('Attendance')->andReturn(true);

        $attendance = (new ModuleCatalog(new ModuleStateResolver($states)))
            ->resolve(base_path('Modules/Attendance'));

        $account = $attendance;
        $account['name'] = 'Account';
        $account['enabled'] = true;
        $account['depends'] = [];
        $account['source'] = 'runtime';

        (new ModuleGraphValidator)->validate(collect([$account, $attendance]));

        $this->assertTrue($attendance['enabled']);
        $this->assertSame('runtime', $attendance['source']);
    }

    public function test_attendance_cannot_be_enabled_while_account_is_disabled(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->once()->with('Attendance')->andReturn(true);

        $attendance = (new ModuleCatalog(new ModuleStateResolver($states)))
            ->resolve(base_path('Modules/Attendance'));

        $account = $attendance;
        $account['name'] = 'Account';
        $account['enabled'] = false;
        $account['depends'] = [];

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
        $this->assertSame(12, $config['privacy']['raw_gps_retention_months']);
    }
}
