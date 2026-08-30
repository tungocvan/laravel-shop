<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminCustomerOwnershipCleanupContractTest extends TestCase
{
    public function test_legacy_admin_customer_routes_remain_absent(): void
    {
        foreach (['admin.customers.index', 'admin.customers.create', 'admin.customers.show', 'admin.customers.edit'] as $name) {
            $this->assertNull(
                app('router')->getRoutes()->getByName($name),
                "Legacy Admin Customer route returned: {$name}"
            );
        }
    }

    public function test_account_admin_routes_remain_canonical_account_runtime(): void
    {
        foreach (['admin.accounts.index', 'admin.accounts.create', 'admin.accounts.edit'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing canonical Account route [{$name}].");
            $this->assertStringContainsString(
                'Modules\\Account\\Http\\Controllers\\AccountController',
                $route->getActionName()
            );
            $this->assertContains('auth:admin', $route->gatherMiddleware());
        }
    }

    public function test_legacy_admin_customer_runtime_remains_absent(): void
    {
        foreach ([
            'Modules/Admin/Http/Controllers/CustomerController.php',
            'Modules/Admin/Livewire/Customers/CustomerCreate.php',
            'Modules/Admin/Livewire/Customers/CustomerDetail.php',
            'Modules/Admin/Livewire/Customers/CustomerTable.php',
            'Modules/Admin/resources/views/livewire/customers/customer-create.blade.php',
            'Modules/Admin/resources/views/livewire/customers/customer-detail.blade.php',
            'Modules/Admin/resources/views/livewire/customers/customer-table.blade.php',
            'Modules/Admin/resources/views/pages/customers/create.blade.php',
            'Modules/Admin/resources/views/pages/customers/index.blade.php',
            'Modules/Admin/resources/views/pages/customers/show.blade.php',
        ] as $file) {
            $this->assertFileDoesNotExist(base_path($file), "Legacy Admin Customer runtime returned: {$file}");
        }
    }

    public function test_account_domain_owns_account_and_customer_profile_runtime(): void
    {
        foreach ([
            'Modules/Account/Http/Controllers/AccountController.php',
            'Modules/Account/Livewire/Accounts/Index.php',
            'Modules/Account/Livewire/Accounts/Form.php',
            'Modules/Account/Models/User.php',
            'Modules/Account/Models/CustomerProfile.php',
            'Modules/Account/Services/AccountService.php',
        ] as $file) {
            $this->assertFileExists(base_path($file), "Missing canonical Account ownership artifact: {$file}");
        }

        $form = file_get_contents(base_path('Modules/Account/Livewire/Accounts/Form.php'));
        $service = file_get_contents(base_path('Modules/Account/Services/AccountService.php'));

        $this->assertStringContainsString("public string $account_type = 'customer';", $form);
        $this->assertStringContainsString('customerProfile', $form);
        $this->assertStringContainsString('AccountService::class', $form);
        $this->assertStringContainsString('CustomerProfile', $service);
    }

    public function test_user_domain_keeps_user_address_model_and_schema_contract(): void
    {
        $modelPath = base_path('Modules/User/Models/UserAddress.php');
        $migrationPath = base_path('Modules/User/database/migrations/-0001_11_30_000009_create_user_addresses_table.php');

        $this->assertFileExists($modelPath);
        $this->assertFileExists($migrationPath);

        $model = file_get_contents($modelPath);

        $this->assertStringContainsString("protected $table = 'user_addresses';", $model);
        $this->assertStringContainsString('Modules\\User\\Models', $model);
    }

    public function test_order_history_is_not_reimplemented_in_account_or_admin_customer_cleanup(): void
    {
        $accountForm = file_get_contents(base_path('Modules/Account/Livewire/Accounts/Form.php'));
        $accountIndex = file_get_contents(base_path('Modules/Account/Livewire/Accounts/Index.php'));

        $this->assertStringNotContainsString("withSum('orders'", $accountForm);
        $this->assertStringNotContainsString("withSum('orders'", $accountIndex);
        $this->assertStringNotContainsString("->orders()", $accountForm);
        $this->assertStringNotContainsString("->orders()", $accountIndex);
    }

    public function test_p0_database_service_remains_quarantined_not_deleted_by_customer_cleanup(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));

        $adminRoutes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringNotContainsString('DatabaseService', $adminRoutes);
        $this->assertStringNotContainsString('DatabaseController', $adminRoutes);
    }
}
