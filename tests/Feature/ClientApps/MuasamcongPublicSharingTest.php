<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MuasamcongPublicSharingTest extends TestCase
{
    public function test_public_share_route_is_public_and_client_share_creation_is_protected(): void
    {
        $public = Route::getRoutes()->getByName('public.muasamcong.drug-share');
        $this->assertNotNull($public);
        $this->assertSame('share/muasamcong/drug/{token}', $public->uri());
        $this->assertNotContains('auth:web', $public->gatherMiddleware());

        $store = Route::getRoutes()->getByName('client.muasamcong.drug-pricing.share');
        $this->assertNotNull($store);
        $this->assertContains('auth:web', $store->gatherMiddleware());
        $this->assertContains('client.application:muasamcong', $store->gatherMiddleware());
        $this->assertContains('client.feature:muasamcong,drug-pricing', $store->gatherMiddleware());
    }
}
