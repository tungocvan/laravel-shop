<?php

namespace Tests\Feature\Attendance;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Attendance\Services\AttendanceGeocodingService;
use Tests\TestCase;

class AttendanceReleaseReadinessTest extends TestCase
{
    public function test_geocoder_can_be_disabled_without_affecting_manual_location_configuration(): void
    {
        config()->set('attendance.attendance.geocoding.enabled', false);
        Http::fake();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tìm tọa độ từ địa chỉ hiện đang tắt.');

        (new AttendanceGeocodingService)->geocode('1 Nguyễn Huệ, Quận 1');
    }

    public function test_geocoder_uses_configured_endpoint_and_returns_only_required_coordinates(): void
    {
        config()->set('attendance.attendance.geocoding.enabled', true);
        config()->set('attendance.attendance.geocoding.endpoint', 'https://maps.example.test/search');
        config()->set('attendance.attendance.geocoding.timeout_seconds', 3);

        Http::fake([
            'maps.example.test/*' => Http::response([[
                'lat' => '10.7769',
                'lon' => '106.7009',
                'display_name' => 'Văn phòng chính',
                'provider_internal_payload' => ['must_not_escape' => true],
            ]]),
        ]);

        $result = (new AttendanceGeocodingService)->geocode('Văn phòng chính');

        $this->assertSame([
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'display_name' => 'Văn phòng chính',
        ], $result);
        $this->assertArrayNotHasKey('provider_internal_payload', $result);

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://maps.example.test/search'));
    }

    public function test_geocoder_connection_failure_is_presented_as_domain_failure(): void
    {
        config()->set('attendance.attendance.geocoding.enabled', true);
        config()->set('attendance.attendance.geocoding.endpoint', 'https://maps.example.test/search');

        Http::fake(fn () => throw new ConnectionException('offline'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Không thể kết nối dịch vụ bản đồ.');

        (new AttendanceGeocodingService)->geocode('Văn phòng chính');
    }

    public function test_demo_routes_are_registered_only_for_local_or_testing_environments(): void
    {
        $routes = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

        $this->assertStringContainsString("if (app()->environment(['local', 'testing']))", $routes);
        $this->assertStringContainsString("Route::get('/demo-operations'", $routes);
        $this->assertStringContainsString("Route::post('/demo-operations/seed'", $routes);
        $this->assertStringContainsString("Route::delete('/demo-operations/reset'", $routes);
    }

    public function test_scheduler_runs_privacy_cleanup_only_when_attendance_is_effectively_enabled(): void
    {
        $schedule = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString("config('modules.registry.Attendance.enabled', false)", $schedule);
        $this->assertStringContainsString("Schedule::command('attendance:privacy-purge')", $schedule);
        $this->assertStringContainsString("->dailyAt('02:30')", $schedule);
        $this->assertStringContainsString('->withoutOverlapping()', $schedule);
    }
}
