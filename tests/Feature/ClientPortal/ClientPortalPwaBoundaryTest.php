<?php

namespace Tests\Feature\ClientPortal;

use Tests\TestCase;

class ClientPortalPwaBoundaryTest extends TestCase
{
    public function test_client_portal_owns_its_manifest_route(): void
    {
        $response = $this->get(route('client.apps.manifest'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonStructure([
                'name',
                'short_name',
                'start_url',
                'scope',
                'display',
                'theme_color',
                'background_color',
                'icons',
            ]);

        $this->assertSame('/my-apps', $response->json('scope'));
    }
}
