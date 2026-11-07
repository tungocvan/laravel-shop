<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\LookupWishlistService;
use Tests\TestCase;

class PharmaUnifiedLookupWishlistTest extends TestCase
{
    public function test_routes_are_feature_protected_and_mutations_are_not_get(): void
    {
        foreach ([
            'client.pharma.lookups.wishlist' => 'GET',
            'client.pharma.lookups.wishlist.show' => 'GET',
            'client.pharma.lookups.wishlist.toggle' => 'POST',
            'client.pharma.lookups.wishlist.remove' => 'DELETE',
        ] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains($method, $route->methods());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.feature:pharma,lookups', $route->gatherMiddleware());
            if (in_array($method, ['POST', 'DELETE'], true)) {
                $this->assertNotContains('GET', $route->methods());
            }
        }
    }

    public function test_four_sources_share_heart_partial_and_distinct_source_identifiers(): void
    {
        $this->assertSame([
            'dav_price', 'traditional_price', 'dav_registration', 'traditional_registration',
        ], array_keys(LookupWishlistService::SOURCES));

        foreach ([
            'dav-prices.blade.php',
            'traditional-prices.blade.php',
            'drug-registrations.blade.php',
        ] as $file) {
            $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/'.$file));
            $this->assertStringContainsString('lookup-wishlist-heart', $view);
        }
        $partial = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/partials/lookup-wishlist-heart.blade.php'));
        $this->assertStringContainsString('client.pharma.lookups.wishlist.toggle', $partial);
        $this->assertStringContainsString('@csrf', $partial);
        $this->assertStringContainsString('name="source_type"', $partial);
        $this->assertStringContainsString('name="source_id"', $partial);
        $this->assertStringContainsString('in_array($item[', $partial);
    }

    public function test_existing_award_wishlist_is_read_only_union_and_not_migrated(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaLookupWishlistController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/LookupWishlistService.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_07_170000_create_pharma_lookup_wishlists_table.php'));
        $hub = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookups.blade.php'));

        $this->assertStringContainsString("DB::table('pharma_award_wishlists')", $controller);
        $this->assertStringContainsString("->unionAll(", $controller);
        $this->assertStringContainsString("'public_award' AS source_type", $controller);
        $this->assertStringContainsString("->where('user_id', \$userId)", $controller);
        $this->assertStringContainsString("->where('user_id', \$userId)", $service);
        $this->assertStringContainsString("Schema::create('pharma_lookup_wishlists'", $migration);
        $this->assertStringNotContainsString('Schema::dropIfExists(\'pharma_award_wishlists\'', $migration);
        $this->assertStringContainsString('pharma_lookup_wishlist_identity_unique', $migration);
        $this->assertStringContainsString('client.pharma.lookups.wishlist', $hub);
    }

    public function test_wishlist_does_not_create_or_sync_medicines(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaLookupWishlistController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/LookupWishlistService.php'));
        $this->assertStringNotContainsString('MedicineService', $controller);
        $this->assertStringNotContainsString('MedicineService', $service);
        $this->assertStringContainsString("hash_equals((string) \$row['source_id'], \$sourceId)", $controller);
        $this->assertStringContainsString("->firstOrFail()", $controller);
        $this->assertStringContainsString('lockForUpdate()', $service);
    }
}
