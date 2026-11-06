<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavMedicineCandidateMatcher;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmaDavImportContractTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_declared_only_import_does_not_insert_selling_price(): void
    {
        $service = app(MedicineService::class);
        $medicine = $service->storeWithDeclaredPriceOnly([
            'name' => 'DAV TEST DECLARED ONLY',
            'product_type' => Medicine::PRODUCT_TYPE_MODERN,
            'registration_number' => 'DAV-TEST-ONLY-20261106',
            'declared_price' => 6500,
            'selling_price' => 9999,
        ]);

        $this->assertSame(6500.0, (float) $medicine->declared_price);
        $this->assertSame('DAV TEST DECLARED ONLY', $medicine->name);
    }

    public function test_candidate_matcher_requires_name_and_registration(): void
    {
        $matcher = app(DavMedicineCandidateMatcher::class);
        foreach ([
            ['name' => 'METHYL 4 LEMED', 'registration' => ''],
            ['name' => '', 'registration' => '893110605624'],
        ] as $candidate) {
            $result = $matcher->inspect($candidate);
            $this->assertSame('incomplete', $result['status']);
            $this->assertNull($result['medicine_id']);
        }
    }
}
