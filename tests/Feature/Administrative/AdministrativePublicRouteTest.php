<?php

namespace Tests\Feature\Administrative;

use Tests\TestCase;

class AdministrativePublicRouteTest extends TestCase
{
    public function test_public_routes_do_not_require_authentication(): void
    {
        $routes = app('router')->getRoutes();
        $expected = [
            'administrative.public.index' => 'thu-tuc-hanh-chinh',
            'administrative.public.show' => 'thu-tuc-hanh-chinh/{procedure}',
            'administrative.public.template.download' => 'thu-tuc-hanh-chinh/{procedure}/bieu-mau',
            'administrative.public.submit' => 'thu-tuc-hanh-chinh/{procedure}/nop-ho-so',
            'administrative.public.success' => 'thu-tuc-hanh-chinh/nop-thanh-cong/{receipt}',
            'administrative.public.receipt.download' => 'thu-tuc-hanh-chinh/nop-thanh-cong/{receipt}/bien-nhan.pdf',
        ];

        foreach ($expected as $name => $uri) {
            $route = $routes->getByName($name);
            $this->assertNotNull($route);
            $this->assertSame($uri, $route->uri());
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertNotContains('auth', $route->gatherMiddleware());
            $this->assertNotContains('auth:admin', $route->gatherMiddleware());
        }

        $success = $routes->getByName('administrative.public.success');
        $this->assertSame('[a-f0-9]{48}', $success->wheres['receipt'] ?? null);
        $this->assertContains('cache.headers:no_store;private', $success->gatherMiddleware());
    }
}
