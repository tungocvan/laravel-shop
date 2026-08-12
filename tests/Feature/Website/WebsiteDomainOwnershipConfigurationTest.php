<?php

namespace Tests\Feature\Website;

use Modules\Category\Models\Category;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Post\Models\Post;
use Modules\Product\Models\Product;
use Modules\User\Models\UserAddress;
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
        $checkout = file_get_contents(base_path('Modules/Order/Services/CheckoutService.php'));

        $this->assertStringNotContainsString('Modules\\Website\\Models\\WpProduct', $checkout);
        $this->assertStringNotContainsString('Modules\\Website\\Models\\Order', $checkout);
        $this->assertStringContainsString('Modules\\Product\\Models\\Product', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\Order', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\OrderItem', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Models\\OrderHistory', $checkout);
        $this->assertStringContainsString('Modules\\Order\\Contracts\\CheckoutContext', $checkout);
        $this->assertStringNotContainsString('Modules\\Website', $checkout);
    }

    public function test_slice_2b_uses_canonical_post_and_category_models(): void
    {
        foreach ([
            'Modules/Website/Services/HomepageContentService.php',
            'Modules/Post/Services/PostService.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringNotContainsString('Modules\\Website\\Models\\Post', $contents, $file);
            $this->assertStringContainsString('Modules\\Post\\Models\\Post', $contents, $file);
        }

        $blogHighlight = file_get_contents(base_path('Modules/Website/Livewire/Home/BlogHighlight.php'));
        $this->assertStringContainsString('HomepageContentService', $blogHighlight);
        $this->assertStringNotContainsString('::query(', $blogHighlight);

        foreach (['PostList.php', 'PostDetail.php'] as $component) {
            $contents = file_get_contents(base_path('Modules/Website/Livewire/Post/'.$component));
            $this->assertStringContainsString('Modules\\Post\\Services\\PostService', $contents);
            $this->assertStringNotContainsString('::query(', $contents);
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

        $userSource = file_get_contents(base_path('app/Models/User.php'));
        $this->assertStringContainsString('Modules\\Order\\Models\\Order', $userSource);
        $this->assertStringNotContainsString('Modules\\Website\\Models\\Order', $userSource);
    }

    public function test_slice_2d_uses_user_owned_address_contract(): void
    {
        $address = new UserAddress([
            'address' => '1 Main Street',
            'ward' => 'Ward 1',
            'district' => 'District 1',
            'city' => 'Ho Chi Minh City',
            'is_default' => 1,
        ]);

        $this->assertSame('user_addresses', $address->getTable());
        $this->assertTrue($address->is_default);
        $this->assertSame('1 Main Street, Ward 1, District 1, Ho Chi Minh City', $address->full_address);
        $this->assertTrue(method_exists($address, 'user'));

        foreach ([
            'app/Models/User.php',
            'Modules/Website/Livewire/Account/Profile/UserAddress.php',
            'Modules/Website/Livewire/Admin/Customers/CustomerDetail.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('Modules\\Website\\Models\\UserAddress', $contents, $file);
            $this->assertStringContainsString('Modules\\User', $contents, $file);
        }

        $service = file_get_contents(base_path('Modules/User/Services/UserAddressService.php'));
        $this->assertStringContainsString('DB::transaction', $service);
        $this->assertStringContainsString("where('user_id', \$userId)", $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
    }

    public function test_slice_2e_removes_only_zero_caller_domain_duplicates(): void
    {
        $removed = [
            'Modules/Website/Models/WpProduct.php',
            'Modules/Website/Models/Category.php',
            'Modules/Website/Models/Post.php',
            'Modules/Website/Models/Order.php',
            'Modules/Website/Models/OrderItem.php',
            'Modules/Website/Models/OrderHistory.php',
            'Modules/Website/Models/UserAddress.php',
            'Modules/Website/Models/Review.php',
            'Modules/Website/Models/Wishlist.php',
            'Modules/Product/Models/Category.php',
            'Modules/Post/Models/Category.php',
            'Modules/Post/Models/Product.php',
            'Modules/Post/Models/Review.php',
            'Modules/Post/Models/Wishlist.php',
            'Modules/Order/Models/Product.php',
            'Modules/Website/Services/Account/AddressService.php',
        ];

        foreach ($removed as $file) {
            $this->assertFileDoesNotExist(base_path($file), "Legacy duplicate still exists: {$file}");
        }

        $roots = ['app', 'Modules'];
        $legacyNamespaces = [
            'Modules\\Website\\Models\\WpProduct',
            'Modules\\Website\\Models\\Category',
            'Modules\\Website\\Models\\Post',
            'Modules\\Website\\Models\\Order',
            'Modules\\Website\\Models\\UserAddress',
            'Modules\\Website\\Models\\Review',
            'Modules\\Website\\Models\\Wishlist',
            'Modules\\Website\\Services\\Services',
        ];

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                foreach ($legacyNamespaces as $namespace) {
                    $this->assertStringNotContainsString($namespace, $contents, $file->getPathname());
                }
            }
        }
    }

    public function test_slice_2f_website_customer_ui_uses_user_owned_write_services(): void
    {
        $contracts = [
            'Modules/Website/Livewire/Account/Profile/UserProfile.php' => 'Modules\\User\\Services\\UserProfileService',
            'Modules/Website/Livewire/Admin/Customers/CustomerCreate.php' => 'Modules\\User\\Services\\CustomerService',
            'Modules/Website/Livewire/Admin/Customers/CustomerDetail.php' => 'Modules\\User\\Services\\CustomerService',
            'Modules/Website/Livewire/Admin/Customers/CustomerTable.php' => 'Modules\\User\\Services\\CustomerService',
        ];

        foreach ($contracts as $file => $service) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringContainsString($service, $contents, $file);
            $this->assertStringNotContainsString('Modules\\Website\\Services\\Account\\ProfileService', $contents, $file);
        }

        $this->assertFileDoesNotExist(base_path('Modules/Website/Services/Account/ProfileService.php'));
        $this->assertFileExists(base_path('Modules/User/Services/UserProfileService.php'));
        $this->assertFileExists(base_path('Modules/User/Services/CustomerService.php'));
    }

    public function test_slice_2g_order_owns_checkout_workflow_through_website_adapter(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Website/Services/CheckoutService.php'));
        $this->assertFileExists(base_path('Modules/Order/Services/CheckoutService.php'));

        foreach ([
            'Modules/Website/Livewire/Checkout/CheckoutForm.php',
            'Modules/Website/Http/Controllers/CheckoutController.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringContainsString('Modules\\Order\\Services\\CheckoutService', $contents, $file);
            $this->assertStringNotContainsString('Modules\\Website\\Services\\CheckoutService', $contents, $file);
        }

        $orderWorkflow = file_get_contents(base_path('Modules/Order/Services/CheckoutService.php'));
        $this->assertStringContainsString('CheckoutContext', $orderWorkflow);
        $this->assertStringContainsString('PaymentResultVerifier', $orderWorkflow);
        $this->assertStringNotContainsString('Modules\\Website', $orderWorkflow);

        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $this->assertStringContainsString('CheckoutContext::class, WebsiteCheckoutContext::class', $provider);
    }
}
