<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Models\UserAddress as AdminUserAddress;
use Modules\Admin\Services\AddressService as AdminAddressService;
use Modules\User\Models\UserAddress;
use Modules\User\Services\UserAddressService;
use ReflectionClass;
use Tests\TestCase;

class AdminAddressOwnershipContractTest extends TestCase
{
    public function test_user_module_owns_canonical_address_model(): void
    {
        $this->assertTrue(is_subclass_of(AdminUserAddress::class, UserAddress::class));
        $this->assertSame(UserAddress::class, get_parent_class(AdminUserAddress::class));

        $legacy = file_get_contents(base_path('Modules/Admin/Models/UserAddress.php'));

        $this->assertNotFalse($legacy);
        $this->assertStringContainsString('@deprecated', $legacy);
        $this->assertStringNotContainsString("protected \$table", $legacy);
        $this->assertStringNotContainsString('protected $fillable', $legacy);
    }

    public function test_admin_address_service_is_only_a_compatibility_facade(): void
    {
        $legacy = file_get_contents(base_path('Modules/Admin/Services/AddressService.php'));

        $this->assertNotFalse($legacy);
        $this->assertStringContainsString('@deprecated', $legacy);
        $this->assertStringContainsString('UserAddressService', $legacy);
        $this->assertStringNotContainsString('Modules\\Admin\\Models\\UserAddress', $legacy);
        $this->assertStringNotContainsString('UserAddress::query()', $legacy);
        $this->assertStringNotContainsString('UserAddress::where(', $legacy);
    }

    public function test_legacy_service_preserves_historical_public_api(): void
    {
        $reflection = new ReflectionClass(AdminAddressService::class);

        foreach (['getUserAddresses', 'create', 'update', 'delete', 'setDefault'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing legacy AddressService method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic());
        }
    }

    public function test_canonical_user_service_owns_address_mutations(): void
    {
        $reflection = new ReflectionClass(UserAddressService::class);

        foreach (['forUser', 'findForUser', 'create', 'update', 'delete', 'setDefault'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing canonical UserAddressService method [{$method}].");
        }
    }
}
