<?php

namespace Tests\Feature\Category;

use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Category\Models\Category;
use Modules\Category\Models\CategoryType;
use Modules\Category\Services\ImportExport;
use Tests\TestCase;

final class CategoryImportBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The import service explicitly checks the admin guard and permission.
        Auth::guard('admin')->setUser(new class extends AuthenticatableUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return true;
            }
        });

        foreach (['medicine', 'post', 'product'] as $type) {
            CategoryType::query()->firstOrCreate(
                ['type' => $type],
                ['title' => ucfirst($type), 'is_active' => true]
            );
        }
    }

    public function test_mixed_types_and_out_of_order_parents_import_with_correct_parent_ids(): void
    {
        $report = $this->importCsv([
            ['Medicine Child', 'medicine-child', 'medicine', 'medicine-root'],
            ['Post Child', 'post-child', 'post', 'post-root'],
            ['Product Root', 'product-root', 'product', ''],
            ['Medicine Root', 'medicine-root', 'medicine', ''],
            ['Post Root', 'post-root', 'post', ''],
            ['Product Child', 'product-child', 'product', 'product-root'],
        ]);

        $this->assertTrue($report['success'], json_encode($report['errors']));
        $this->assertSame(6, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
        $this->assertSame(6, Category::query()->count());

        foreach (['medicine', 'post', 'product'] as $type) {
            $root = Category::query()->where('type', $type)->where('slug', $type.'-root')->firstOrFail();
            $child = Category::query()->where('type', $type)->where('slug', $type.'-child')->firstOrFail();
            $this->assertNull($root->parent_id);
            $this->assertEquals($root->id, $child->parent_id);
        }
    }

    public function test_dry_run_validates_dependency_order_but_persists_nothing(): void
    {
        $report = $this->importCsv([
            ['Medicine Child', 'medicine-child', 'medicine', 'medicine-root'],
            ['Medicine Root', 'medicine-root', 'medicine', ''],
            ['Post Root', 'post-root', 'post', ''],
        ], ['dry_run' => true]);

        $this->assertTrue($report['success'], json_encode($report['errors']));
        $this->assertSame(3, $report['success_rows']);
        $this->assertSame(0, Category::query()->count());
    }

    public function test_missing_same_type_parent_rolls_back_all_rows(): void
    {
        $report = $this->importCsv([
            ['Medicine Root', 'medicine-root', 'medicine', ''],
            ['Medicine Child', 'medicine-child', 'medicine', 'post-root'],
            ['Post Root', 'post-root', 'post', ''],
        ]);

        $this->assertFalse($report['success']);
        $this->assertGreaterThan(0, $report['error_rows']);
        $this->assertSame(0, Category::query()->count());
        $this->assertContains('parent_slug', array_column($report['errors'], 'column'));
    }

    /**
     * @param array<int, array{string, string, string, string}> $rows
     */
    private function importCsv(array $rows, array $options = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'category-import-');
        $csvPath = $path.'.csv';
        rename($path, $csvPath);

        try {
            $file = fopen($csvPath, 'wb');
            fputcsv($file, ['name', 'slug', 'type', 'parent_slug']);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            return app(ImportExport::class)->import($csvPath, $options);
        } finally {
            @unlink($csvPath);
        }
    }
}
