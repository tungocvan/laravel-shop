<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WishlistIconOwnershipTest extends TestCase
{
    public function test_wishlist_icon_uses_product_owned_wishlist_service(): void
    {
        $contents = file_get_contents(base_path('Modules/Website/Livewire/Wishlist/WishlistIcon.php'));

        $this->assertStringContainsString('Modules\\Product\\Services\\WishlistService', $contents);
        $this->assertStringNotContainsString('Modules\\Website\\Services\\WishlistService', $contents);
        $this->assertFileExists(base_path('Modules/Product/Services/WishlistService.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Website/Services/WishlistService.php'));
    }
}
