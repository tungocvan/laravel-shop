<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\WebsiteController;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage_route_is_registered_to_website_controller(): void
    {
        $route = Route::getRoutes()->getByName('home');

        $this->assertNotNull($route);
        $this->assertSame('/', $route->uri());
        $this->assertSame(WebsiteController::class.'@home', $route->getActionName());
        $this->assertContains('web', $route->gatherMiddleware());
    }
}
