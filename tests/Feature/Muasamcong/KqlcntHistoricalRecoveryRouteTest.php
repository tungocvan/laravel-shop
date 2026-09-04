<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KqlcntHistoricalRecoveryRouteTest extends TestCase
{
    public function test_recovery_routes_enforce_expected_permissions_and_methods(): void
    {
        $view = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery');
        $template = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.template');
        $supplement = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.supplement');
        $supplementDownload = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.supplement.download');
        $export = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.export');
        $enrich = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.enrich');
        $upload = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.upload');
        $supplementUpload = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.supplement.upload');
        $batch = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.batch');
        $preview = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.preview');
        $confirm = Route::getRoutes()->getByName('muasamcong.contractors.kqlcnt-recovery.confirm');

        foreach ([$view, $template, $supplement, $supplementDownload, $export, $enrich, $upload, $supplementUpload, $batch, $preview, $confirm] as $route) {
            $this->assertNotNull($route);
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('permission:view_muasamcong,admin', $route->gatherMiddleware());
        }

        foreach ([$enrich, $upload, $supplementUpload, $batch, $preview, $confirm] as $route) {
            $this->assertContains('permission:muasamcong.pricing.sync,admin', $route->gatherMiddleware());
        }

        foreach ([$view, $template, $supplement, $supplementDownload, $export] as $route) {
            $this->assertNotContains('permission:muasamcong.pricing.sync,admin', $route->gatherMiddleware());
        }

        $this->assertSame(['GET', 'HEAD'], $view->methods());
        $this->assertSame(['GET', 'HEAD'], $supplement->methods());
        $this->assertSame(['GET', 'HEAD'], $supplementDownload->methods());
        $this->assertSame(['POST'], $supplementUpload->methods());
        $this->assertSame(['POST'], $export->methods());
        $this->assertSame(['POST'], $enrich->methods());
        $this->assertSame(['POST'], $upload->methods());
        $this->assertSame(['GET', 'HEAD'], $batch->methods());
        $this->assertSame(['POST'], $preview->methods());
        $this->assertSame(['POST'], $confirm->methods());
    }
}
