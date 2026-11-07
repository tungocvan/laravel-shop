<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PharmaWishlistMedicineSyncContractTest extends TestCase
{
    public function test_import_routes_are_guarded_and_confirmation_is_post_only(): void
    {
        foreach (['client.pharma.lookups.wishlist.import.preview' => 'GET',
                  'client.pharma.lookups.wishlist.import.store' => 'POST'] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.feature:pharma,lookups', $route->gatherMiddleware());
        }
        $this->assertNotContains('GET', Route::getRoutes()
            ->getByName('client.pharma.lookups.wishlist.import.store')->methods());
    }

    public function test_registration_import_uses_source_reverification_and_canonical_service(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/RegistrationWishlistMedicineSync.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaWishlistMedicineSyncController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookup-wishlist-import-preview.blade.php'));
        $this->assertStringContainsString('findOnPage(', $service);
        $this->assertStringContainsString("['name', 'registration', 'source_id']", $service);
        $this->assertStringContainsString("['withdrawn']", $service);
        $this->assertStringContainsString("['active']", $service);
        $this->assertStringContainsString('Cache::lock(', $service);
        $this->assertStringContainsString('storeWithDeclaredPriceOnly(', $service);
        $this->assertStringContainsString("'declared_price' => null", $service);
        $this->assertStringContainsString("->where('user_id', ", $controller);
        $this->assertStringContainsString("'client.pharma.products.create'", $controller);
        $this->assertStringContainsString("'confirmed' => ['accepted']", $controller);
        $this->assertStringContainsString('name="unit"', $view);
        $this->assertStringContainsString('name="confirmed"', $view);
    }

    public function test_price_imports_reuse_existing_workflows_and_award_wishlist_is_unchanged(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaWishlistMedicineSyncController.php'));
        $wishlist = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookup-wishlist.blade.php'));
        $this->assertStringContainsString("'client.pharma.dav-prices.import.preview'", $controller);
        $this->assertStringContainsString("'client.pharma.traditional-prices.import.preview'", $controller);
        $this->assertStringContainsString('return redirect()->route($route', $controller);
        $this->assertStringContainsString('@unless($isAward)', $wishlist);
        $this->assertStringContainsString('không tự động tạo thuốc khi lưu quan tâm', $wishlist);
        $this->assertStringContainsString('client.pharma.award-prices.wishlist', $wishlist);
    }
}
