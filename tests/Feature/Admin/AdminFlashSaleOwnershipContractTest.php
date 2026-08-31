<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminFlashSaleOwnershipContractTest extends TestCase
{
    public function test_flash_sale_management_surface_uses_website_domain_and_product_service(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php'));

        $this->assertStringContainsString('use Modules\\Website\\Services\\FlashSaleService;', $source);
        $this->assertStringContainsString('use Modules\\Product\\Services\\ProductService;', $source);
        $this->assertStringContainsString('ProductService $products', $source);
        $this->assertStringContainsString('$service->findWithProducts($id)', $source);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/FlashSale/FlashSaleManager.php'));
    }

    public function test_legacy_admin_flash_sale_classes_are_compatibility_only(): void
    {
        $model = file_get_contents(base_path('Modules/Admin/Models/FlashSale.php'));
        $item = file_get_contents(base_path('Modules/Admin/Models/FlashSaleItem.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/FlashSaleService.php'));

        $this->assertStringContainsString('@deprecated', $model);
        $this->assertStringContainsString('extends \\Modules\\Website\\Models\\FlashSale', $model);
        $this->assertStringNotContainsString('protected $table', $model);
        $this->assertStringNotContainsString('protected $fillable', $model);

        $this->assertStringContainsString('@deprecated', $item);
        $this->assertStringContainsString('extends \\Modules\\Website\\Models\\FlashSaleItem', $item);
        $this->assertStringNotContainsString('protected $table', $item);
        $this->assertStringNotContainsString('protected $fillable', $item);

        $this->assertStringContainsString('@deprecated', $service);
        $this->assertStringContainsString('extends \\Modules\\Website\\Services\\FlashSaleService', $service);
        $this->assertStringNotContainsString('function createFlashSale', $service);
        $this->assertStringNotContainsString('function updateFlashSale', $service);
        $this->assertStringNotContainsString('function delete', $service);
    }

    public function test_website_flash_sale_domain_and_product_boundary_are_canonical(): void
    {
        $websiteModel = file_get_contents(base_path('Modules/Website/Models/FlashSale.php'));
        $websiteItem = file_get_contents(base_path('Modules/Website/Models/FlashSaleItem.php'));
        $websiteService = file_get_contents(base_path('Modules/Website/Services/FlashSaleService.php'));
        $product = file_get_contents(base_path('Modules/Product/Models/Product.php'));

        $this->assertStringContainsString("protected \$table = 'wp_flash_sales'", $websiteModel);
        $this->assertStringContainsString("protected \$table = 'wp_flash_sale_items'", $websiteItem);
        $this->assertStringContainsString('use Modules\\Product\\Models\\Product;', $websiteItem);
        $this->assertStringContainsString('function findWithProducts', $websiteService);
        $this->assertStringContainsString('function createFlashSale', $websiteService);
        $this->assertStringContainsString('function updateFlashSale', $websiteService);
        $this->assertStringContainsString("protected \$table = 'wp_products'", $product);
        $this->assertStringContainsString('function scopeActive', $product);
    }

    public function test_coupon_and_affiliate_surfaces_use_canonical_website_ownership_while_database_stays_quarantined(): void
    {
        $couponIndex = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/coupons/index.blade.php'));

        $this->assertStringContainsString('<livewire:website.admin.coupon.coupon-table />', $couponIndex);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Marketing/CouponForm.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Marketing/CouponTable.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Affiliate/CommissionList.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Affiliate/CommissionMatrix.php'));
        $this->assertFileExists(base_path('Modules/Website/Livewire/Admin/Affiliate/CommissionList.php'));
        $this->assertFileExists(base_path('Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php'));
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));
    }
}
