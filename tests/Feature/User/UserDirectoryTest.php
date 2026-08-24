<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\User\Contracts\UserDirectory;
use Modules\User\Data\UserIdentity;
use Modules\User\Services\AuthUserDirectory;
use ReflectionClass;
use Tests\TestCase;

class UserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_binds_the_stable_user_directory_contract(): void
    {
        $this->assertInstanceOf(AuthUserDirectory::class, app(UserDirectory::class));
        $this->assertTrue((new ReflectionClass(UserIdentity::class))->isReadOnly());
    }

    public function test_it_returns_only_active_non_deleted_safe_identities(): void
    {
        $active = $this->createUser('Active User', 'active@example.test', true);
        $inactive = $this->createUser('Inactive User', 'inactive@example.test', false);
        $deleted = $this->createUser('Deleted User', 'deleted@example.test', true, now());

        $directory = app(UserDirectory::class);
        $identity = $directory->findActive($active);

        $this->assertSame($active, $identity?->id);
        $this->assertSame('Active User', $identity?->displayName);
        $this->assertSame('a*****@example.test', $identity?->maskedEmail);
        $this->assertTrue($identity?->active);
        $this->assertNull($directory->findActive($inactive));
        $this->assertNull($directory->findActive($deleted));
    }

    public function test_many_lookup_deduplicates_and_preserves_requested_order(): void
    {
        $first = $this->createUser('First User', 'first@example.test', true);
        $second = $this->createUser('Second User', 'second@example.test', true);
        $inactive = $this->createUser('Inactive User', 'inactive@example.test', false);

        $identities = app(UserDirectory::class)->findManyActive([
            $second,
            $inactive,
            $first,
            $second,
            'invalid',
        ], 5);

        $this->assertSame([$second, $first], array_column($identities, 'id'));
    }

    public function test_search_is_bounded_and_requires_a_valid_limit(): void
    {
        $this->createUser('Alpha One', 'alpha.one@example.test', true);
        $this->createUser('Alpha Two', 'alpha.two@example.test', true);
        $this->createUser('Alpha Disabled', 'alpha.disabled@example.test', false);

        $this->assertCount(1, app(UserDirectory::class)->searchActive('Alpha', 1));

        $this->expectException(InvalidArgumentException::class);
        app(UserDirectory::class)->searchActive('Alpha', 101);
    }

    private function createUser(string $name, string $email, bool $active, mixed $deletedAt = null): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => null,
            'is_active' => $active,
            'deleted_at' => $deletedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
