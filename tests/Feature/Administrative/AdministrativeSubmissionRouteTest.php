<?php

namespace Tests\Feature\Administrative;

use Tests\TestCase;

class AdministrativeSubmissionRouteTest extends TestCase
{
    public function test_submission_admin_routes_require_named_permissions(): void
    {
        $routes = app('router')->getRoutes();
        $dashboard = $routes->getByName('admin.administrative.dashboard');
        $index = $routes->getByName('admin.administrative.submissions.index');
        $show = $routes->getByName('admin.administrative.submissions.show');
        $download = $routes->getByName('admin.administrative.submissions.files.download');

        $this->assertSame('admin/administrative', $dashboard?->uri());
        $this->assertSame('admin/administrative/submissions', $index?->uri());
        $this->assertSame('admin/administrative/submissions/{id}', $show?->uri());
        $this->assertSame('admin/administrative/submissions/{submission}/files/{file}', $download?->uri());

        foreach ([$dashboard, $index, $show, $download] as $route) {
            $this->assertContains('auth:admin', $route->gatherMiddleware());
        }

        $this->assertContains('permission:administrative.submission.view,admin', $index->gatherMiddleware());
        $this->assertContains('permission:administrative.submission.view,admin', $show->gatherMiddleware());
        $this->assertContains('permission:administrative.file.download,admin', $download->gatherMiddleware());
        $this->assertSame('[0-9]+', $show->wheres['id'] ?? null);
        $this->assertSame('[0-9]+', $download->wheres['submission'] ?? null);
        $this->assertSame('[0-9]+', $download->wheres['file'] ?? null);
    }
}
