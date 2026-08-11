<?php

namespace Tests\Feature\Website;

use Modules\Category\Models\Category;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Post\Models\Post;
use Modules\Product\Models\Product;
use Tests\TestCase;

class WebsiteDomainOwnershipConfigurationTest extends TestCase
{
    public function test_slice_2a_uses_canonical_product_and_category_models_for_reads(): void
    {
        $files = [
            'Modules/Website/Livewire/Products/ProductList.php',
            'Modules/Website/Livewire/Products/ProductDetail.php',
            'Modules/Website/Livewire/Products/CategoryFilter.php',
            'Modules/Website/Livewire/Home/NewArrivals.php',
            'Modules/Website/Livewire/Home/BestSellers.php',
            'Modules/Website/Livewire/Home/FeaturedProducts.php',
            'Modules/Website/Livewire/Home/CategoryHighlight.php',
            'Modules/Website/Services/ProductService.php',
            'Modules/Website/Services/CategoryService.php',
            'Modules/Website/Livewire/Admin/Home/HomeSettings.php',
            'Modules/Website/Livewire/Dashboard/StatsOverview.php',
            'Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php',
            'Modules/Website/Services/WishlistService.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringNotContainsString('Modules\\Website\\Models\\WpProduct', $contents, $file);
            $this->assertStringNotContainsString('Modules\\Website\\Models\\Category', $contents, $file);
        }

        $homeSettings = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $this->assertStringNotContainsString("DB::table('categories')", $homeSettings);
        $this->assertStringNotContainsString("DB::table('wp_products')", $homeSettings);
    }

    public function test_canonical_models_preserve_storefront_contracts(): void
    {
        $product = new Product;
        $category = new Category;

        $this->assertSame('wp_products', $product->getTable());
        $this->assertSame('categories', $category->getTable());

        foreach (['categories', 'user', 'reviews', 'wishlists'] as $relation) {
            $this->assertTrue(method_exists($product, $relation), "Product missing [{$relation}].");
        }

        foreach (['products', 'children', 'childrenRecursive', 'getAllChildrenIds'] as $contract) {
            $this->assertTrue(method_exists($category, $contract), "Category missing [{$contract}].");
        }

        foreach (['active', 'root', 'roots', 'sorted', 'ofType'] as $scope) {
            $this->assertTrue(method_exists($category, 'scope'.ucfirst($scope)), "Category missing scope [{$scope}].");
        }
    }

    public function test_slice_2c_checkout_uses_canonical_order_and_product_models(): void
    {
        $checkout = file_get_contents(base_path('Modules/Website/Services/CheckoutService.php'));

        $this->assertStringNotContainsString('Modules\\Website\\Models\\WpProduct', $checkout);
        $this->assertStringNotContainsString('Modules\\Website\\Models\\Order', $checkout);
        $this->assertStringContainsString('Modules\\Product\\Models\\Product', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\Order', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\OrderItem', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\OrderHistory', $checkout);
    }

    public function test_slice_2b_uses_canonical_post_and_category_models(): void
    {
        foreach ([
            'Modules/Website/Livewire/Post/PostList.php',
            'Modules/Website/Livewire/Post/PostDetail.php',
            'Modules/Website/Livewire/Home/BlogHighlight.php',
            'Modules/Website/Services/ContentService.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringNotContainsString('Modules\\Website\\Models\\Post', $contents, $file);
            $this->assertStringContainsString('Modules\\Post\\Models\\Post', $contents, $file);
        }

        $post = new Post;
        $category = new Category;

        $this->assertSame('wp_posts', $post->getTable());
        foreach (['categories', 'user', 'tags', 'author'] as $relation) {
            $this->assertTrue(method_exists($post, $relation), "Post missing [{$relation}].");
        }
        $this->assertTrue(method_exists($category, 'posts'));
        $this->assertSame(Category::class, $post->categories()->getRelated()::class);
        $this->assertSame(Post::class, $category->posts()->getRelated()::class);
    }

    public function test_canonical_order_preserves_phase_1a_payment_contract(): void
    {
        $pendingPayment = new Order(['status' => 'pending_payment']);
        $momo = new Order(['payment_method' => 'momo']);
        $bank = new Order(['payment_method' => 'bank_transfer']);
        $item = new OrderItem;

        $this->assertSame('wp_orders', $pendingPayment->getTable());
        $this->assertStringContainsString('Chờ thanh toán', $pendingPayment->status_badge);
        $this->assertSame('Ví MoMo', $momo->payment_method_label);
        $this->assertSame('Chuyển khoản ngân hàng', $bank->payment_method_label);
        $this->assertSame(Product::class, $item->product()->getRelated()::class);

        foreach (['items', 'histories', 'user', 'affiliate', 'recalculateTotalCommission'] as $contract) {
            $this->assertTrue(method_exists($pendingPayment, $contract), "Order missing [{$contract}].");
        }
    }
}
