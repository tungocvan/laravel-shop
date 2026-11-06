<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavMedicineCandidateMatcher;
use Tests\TestCase;

class PharmaDavImportContractTest extends TestCase
{
    public function test_import_routes_are_capability_guarded(): void
    {
        $preview = Route::getRoutes()->getByName('client.pharma.dav-prices.import.preview');
        $store = Route::getRoutes()->getByName('client.pharma.dav-prices.import.store');

        $this->assertNotNull($preview);
        $this->assertNotNull($store);
        $this->assertContains('GET', $preview->methods());
        $this->assertContains('POST', $store->methods());
        $this->assertContains('client.feature:pharma,dav-prices', $preview->gatherMiddleware());
        $this->assertContains('client.feature:pharma,dav-prices', $store->gatherMiddleware());
    }

    public function test_candidate_matcher_requires_name_and_registration(): void
    {
        $matcher = app(DavMedicineCandidateMatcher::class);
        $this->assertSame('incomplete', $matcher->inspect(['name' => 'METHYL 4 LEMED', 'registration' => '']));
        $this->assertSame('incomplete', $matcher->inspect(['name' => '', 'registration' => '893110605624']));
    }
}
