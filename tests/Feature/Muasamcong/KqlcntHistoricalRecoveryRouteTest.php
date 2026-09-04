<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KqlcntHistoricalRecoveryRouteTest extends TestCase
{
    public function test_recovery_routes_enforce_expected_permissions_and_methods(): void
    {
        $view = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery');
        $export = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.export');
        $upload = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.upload');
        $batch = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.batch');
        $preview = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.preview');
        $confirm = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.confirm');

        foreach ([$view, $export, $upload, $batch, $preview, $confirm] as $route) {
            $this->assertNotNull($route);
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('permission:view_muasamcong,admin', $route->gatherMiddleware());
        }

        foreach ([$upload, $batch, $preview, $confirm] as $route) {
            $this->assertContains('permission:muasamcong.pricing.sync,admin', $route->gatherMiddleware());
        }

        $this->assertNotContains('permission:muasamcong.pricing.sync,admin', $view->gatherMiddleware());
        $this->assertNotContains('permission:muasamcong.pricing.sync,admin', $export->gatherMiddleware());

        $this->assertSame('admin/muasamcong/contractors/history/{contractorSearch}/kqlcnt-recovery', $view->uri());
        $this->assertSame(['GET', 'HEAD'], $view->methods());
        $this->assertSame(['POST'], $export->methods());
        $this->assertSame(['POST'], $upload->methods());
        $this->assertSame(['GET', 'HEAD'], $batch->methods());
        $this->assertSame(['POST'], $preview->methods());
        $this->assertSame(['POST'], $confirm->methods());
    }
}
