<?php

namespace Tests\Feature\Administrative;

use Tests\TestCase;

class AdministrativeProcedureRouteTest extends TestCase
{
    public function test_admin_procedure_routes_are_registered_and_protected(): void
    {
        $routes = app('router')->getRoutes();
        $index = $routes->getByName('admin.administrative.procedures.index');
        $create = $routes->getByName('admin.administrative.procedures.create');
        $edit = $routes->getByName('admin.administrative.procedures.edit');
        $download = $routes->getByName('admin.administrative.procedures.template.download');

        $this->assertSame('admin/administrative/procedures', $index?->uri());
        $this->assertSame('admin/administrative/procedures/create', $create?->uri());
        $this->assertSame('admin/administrative/procedures/{id}/edit', $edit?->uri());
        $this->assertSame('admin/administrative/procedures/{id}/template', $download?->uri());
        $this->assertContains('auth:admin', $index->gatherMiddleware());
        $this->assertContains('permission:administrative.procedure.view,admin', $index->gatherMiddleware());
        $this->assertContains('permission:administrative.procedure.create,admin', $create->gatherMiddleware());
        $this->assertContains('permission:administrative.procedure.update,admin', $edit->gatherMiddleware());
        $this->assertContains('permission:administrative.procedure.view,admin', $download->gatherMiddleware());
        $this->assertSame('[0-9]+', $edit->wheres['id'] ?? null);
    }
}
