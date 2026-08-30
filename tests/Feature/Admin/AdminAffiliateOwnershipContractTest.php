<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminAffiliateOwnershipContractTest extends TestCase
{
    public function test_affiliate_admin_route_is_website_owned_and_permission_guarded(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));

        $this->assertStringContainsString('Modules\\Website\\Http\\Controllers\\Admin\\AffiliateController', $routes);
        $this->assertStringContainsString("Route::get('/affiliate', [AffiliateController::class, 'index'])", $routes);
        $this->assertStringContainsString("permission:affiliate.view,admin", $routes);
    }

    public function test_canonical_affiliate_mutations_keep_manage_authorization(): void
    {
        foreach ([
            'Modules/Website/Livewire/Admin/Affiliate/CommissionList.php',
            'Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php',
        ] as $path) {
            $source = file_get_contents(base_path($path));

            $this->assertStringContainsString("authorizeAdminPermission('affiliate.manage')", $source, $path);
        }
    }

    public function test_admin_affiliate_duplicates_are_compatibility_adapters_only(): void
    {
        $expectations = [
            'Modules/Admin/Livewire/Affiliate/CommissionList.php' => 'Modules\\Website\\Livewire\\Admin\\Affiliate\\CommissionList',
            'Modules/Admin/Livewire/Affiliate/CommissionMatrix.php' => 'Modules\\Website\\Livewire\\Admin\\Affiliate\\CommissionMatrix',
            'Modules/Admin/Services/AdminAffiliateService.php' => 'Modules\\Website\\Services\\AdminAffiliateService',
            'Modules/Admin/Services/AffiliateRankService.php' => 'Modules\\Website\\Services\\AffiliateRankService',
            'Modules/Admin/Models/AffiliateScheme.php' => 'Modules\\Website\\Models\\AffiliateScheme',
        ];

        foreach ($expectations as $path => $canonicalClass) {
            $source = file_get_contents(base_path($path));

            $this->assertStringContainsString('@deprecated', $source, $path);
            $this->assertStringContainsString('extends \\'.$canonicalClass, $source, $path);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\AffiliateLevel', $source, $path);
        }
    }

    public function test_commission_list_uses_bounded_admin_pagination_and_level_filter(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Affiliate/CommissionList.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/AdminAffiliateService.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/affiliate/commission-list.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Website/resources/views/vendor/pagination/admin-affiliate.blade.php'));

        $this->assertStringContainsString('[10, 25, 50, 100]', $component);
        $this->assertStringContainsString('[10, 25, 50, 100]', $service);
        $this->assertStringContainsString("where('affiliate_level_id', \$level)", $service);
        $this->assertStringContainsString('wire:model.live="perPage"', $view);
        $this->assertStringContainsString("links('Website::vendor.pagination.admin-affiliate')", $view);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringContainsString('bg-white', $pagination);
        $this->assertStringNotContainsString('>All<', $view);
    }

    public function test_affiliate_schema_is_not_moved_into_admin(): void
    {
        $this->assertFileExists(base_path('Modules/Website/database/migrations/-0001_11_30_000005_create_affiliate_levels_table.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Models/AffiliateLevel.php'));
    }
}
