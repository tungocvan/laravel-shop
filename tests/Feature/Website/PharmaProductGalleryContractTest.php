<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Website\Services\PharmaProductGalleryService;
use Tests\TestCase;

final class PharmaProductGalleryContractTest extends TestCase
{
    public function test_gallery_uses_existing_product_fields_deduplicates_and_hides_missing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/primary.png', 'primary');
        Storage::disk('public')->put('products/second.png', 'second');

        $product = new Product([
            'image' => 'products/primary.png',
            'gallery' => ['products/primary.png', 'products/second.png', 'products/missing.png', '', '../private.png'],
        ]);

        $images = app(PharmaProductGalleryService::class)->images($product);
        $this->assertCount(2, $images);
        $this->assertSame(asset('storage/products/primary.png'), $images[0]);
        $this->assertSame(asset('storage/products/second.png'), $images[1]);
        $this->assertSame([], app(PharmaProductGalleryService::class)->images(new Product(['image' => null, 'gallery' => []])));
    }

    public function test_pharma_product_route_controller_and_blade_gallery_contract(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/ProductController.php'));
        $blade = file_get_contents(base_path('Modules/Website/resources/views/products/show-pharma.blade.php'));

        $this->assertStringContainsString("Route::get('/{slug}', 'show')->name('detail')", $routes);
        $this->assertStringContainsString("'image', 'gallery', 'short_description'", $controller);
        $this->assertStringContainsString("'galleryImages' => \$galleryImages", $controller);
        $this->assertStringContainsString('count($galleryImages) > 0', $blade);
        $this->assertStringContainsString('count($galleryImages) > 1', $blade);
        $this->assertStringContainsString('aria-label="Thư viện ảnh sản phẩm"', $blade);
        $this->assertStringContainsString('x-show="lightbox"', $blade);
    }
}
