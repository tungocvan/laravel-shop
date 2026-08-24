<?php

namespace Tests\Feature\System;

use App\Modules\ModuleMigrationOwnershipVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleMigrationOwnershipVerifierTest extends TestCase
{
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = storage_path('framework/testing/module-ownership-'.uniqid());
        File::ensureDirectoryExists($this->modulePath.'/config');
        File::put($this->modulePath.'/config/migration_ownership.php', <<<'PHP'
<?php

return [
    '2026_01_01_000001_create_fixture' => [
        'tables' => ['ownership_fixture'],
    ],
    '2026_01_01_000002_add_pointer' => [
        'columns' => [
            'ownership_fixture' => ['pointer_id'],
        ],
    ],
];
PHP);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ownership_fixture');
        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_missing_table_blocks_owned_create_migration(): void
    {
        $result = $this->verify();

        $this->assertFalse($result['2026_01_01_000001_create_fixture']['verified']);
        $this->assertSame(['ownership_fixture'], $result['2026_01_01_000001_create_fixture']['missing_tables']);
    }

    public function test_existing_table_verifies_owned_create_migration(): void
    {
        Schema::create('ownership_fixture', function (Blueprint $table): void {
            $table->id();
        });

        $result = $this->verify();

        $this->assertTrue($result['2026_01_01_000001_create_fixture']['verified']);
        $this->assertSame([], $result['2026_01_01_000001_create_fixture']['missing_tables']);
    }

    public function test_missing_owned_column_blocks_alter_migration(): void
    {
        Schema::create('ownership_fixture', function (Blueprint $table): void {
            $table->id();
        });

        $result = $this->verify();

        $this->assertFalse($result['2026_01_01_000002_add_pointer']['verified']);
        $this->assertSame(
            ['ownership_fixture.pointer_id'],
            $result['2026_01_01_000002_add_pointer']['missing_columns'],
        );
    }

    public function test_owned_column_verifies_alter_migration(): void
    {
        Schema::create('ownership_fixture', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pointer_id')->nullable();
        });

        $result = $this->verify();

        $this->assertTrue($result['2026_01_01_000002_add_pointer']['verified']);
        $this->assertSame([], $result['2026_01_01_000002_add_pointer']['missing_columns']);
    }

    private function verify(): array
    {
        return app(ModuleMigrationOwnershipVerifier::class)->verify([
            'name' => 'OwnershipFixture',
            'path' => $this->modulePath,
        ]);
    }
}
