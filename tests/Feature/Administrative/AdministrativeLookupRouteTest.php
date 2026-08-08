<?php

namespace Tests\Feature\Administrative;

use Tests\TestCase;

class AdministrativeLookupRouteTest extends TestCase
{
    public function test_lookup_routes_are_public_and_constrained(): void
    {
        $routes = app('router')->getRoutes();
        $index = $routes->getByName('administrative.lookup.index');
        $show = $routes->getByName('administrative.lookup.show');
        $download = $routes->getByName('administrative.lookup.files.download');

        $this->assertSame('tra-cuu-ho-so', $index?->uri());
        $this->assertSame('tra-cuu-ho-so/{accessToken}', $show?->uri());
        $this->assertSame('tra-cuu-ho-so/{accessToken}/files/{file}', $download?->uri());
        $this->assertNotContains('auth', $index->gatherMiddleware());
        $this->assertNotContains('auth:admin', $show->gatherMiddleware());
        $this->assertSame('[a-f0-9]{64}', $show->wheres['accessToken'] ?? null);
        $this->assertContains('cache.headers:no_store;private', $show->gatherMiddleware());
        $this->assertSame('[0-9]+', $download->wheres['file'] ?? null);
        $this->assertContains('throttle:30,1', $download->gatherMiddleware());
    }
}
