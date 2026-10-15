<?php

namespace Tests\Feature\Category;

use Tests\TestCase;

final class CategoryImportRowContextContractTest extends TestCase
{
    public function test_parent_validation_never_uses_mutable_last_excel_row_context(): void
    {
        $source = file_get_contents(base_path('Modules/Category/Services/ImportExport.php'));

        $this->assertStringNotContainsString('$currentRow', $source);
        $this->assertStringNotContainsString('validateParentSlug(', $source);
        $this->assertStringContainsString("$"."parentSlug === $"."data['slug']", $source);
        $this->assertStringContainsString("'parent_slug' => ['nullable', 'string', 'max:255']", $source);
    }

    public function test_import_resolves_parent_dependencies_and_rolls_back_dry_runs_and_errors(): void
    {
        $source = file_get_contents(base_path('Modules/Category/Services/ImportExport.php'));

        $this->assertStringContainsString('while ($pending !== [])', $source);
        $this->assertStringContainsString("->where('type', $"."data['type'])", $source);
        $this->assertStringContainsString("->where('slug', $"."parentSlug)", $source);
        $this->assertStringContainsString('if ($dryRun || $this->errors !== [])', $source);
        $this->assertStringContainsString('DB::rollBack()', $source);
        $this->assertStringContainsString('DB::commit()', $source);
    }
}
