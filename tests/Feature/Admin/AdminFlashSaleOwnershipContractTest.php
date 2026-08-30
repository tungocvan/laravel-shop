<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminFlashSaleOwnershipContractTest extends TestCase
{
    public function test_flash_sale_management_surface_uses_website_domain_and_product_model(): void
    {
        $source = file_get_contents(base_path('Modules/Admin/Livewire/FlashSale/FlashSaleManager.php'));

        $this->assertStringContainsString('use Modules\\Website\\Models\\FlashSale;', $source);
        $this->assertStringContainsString('use Modules\\Website\\Services\\FlashSaleService;', $source);
        $this->assertStringContainsString('use Modules\\Product\\Models\\Product;', $source);
        $this->assertStringNotContainsString('Modules\\Admin\\Models\\FlashSale', $source);
        $this->assertStringNotContainsString('Modules\\Admin\\Services\\FlashSaleService', $source);
        $this->assertStringNotContainsString("DB::table('wp_products')", $source);
        $this->assertStringContainsString('Product::query()', $source);
        $this->assertStringContainsString('$service->findWithProducts($id)', $source);
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

    public function test_coupon_affiliate_and_database_quarantine_remain_outside_this_slice(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponForm.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponTable.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Affiliate/CommissionList.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Affiliate/CommissionMatrix.php'));
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));
    }
}
