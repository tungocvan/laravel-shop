<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaMedicineCategoryCsvContractTest extends TestCase
{
    public function test_category_csv_routes_are_permission_scoped(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $this->assertStringContainsString("categories-csv.preview", $routes);
        $this->assertStringContainsString("categories-csv.commit", $routes);
        $this->assertStringContainsString("->middleware('can:edit_pharma')", $routes);
    }

    public function test_csv_import_is_category_only_and_atomic(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineCategoryCsvService.php'));
        $this->assertStringContainsString("where('medicine_code', \$code)", $service);
        $this->assertStringContainsString("where('type', 'medicine')", $service);
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('categories()->sync($ids)', $service);
        $this->assertStringContainsString("if (\$raw === '')", $service);
    }

    public function test_csv_ui_requires_preview_before_commit(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/medicine/category-csv.blade.php'));
        $this->assertStringContainsString("route('admin.pharma.medicines.categories-csv.preview')", $view);
        $this->assertStringContainsString("route('admin.pharma.medicines.categories-csv.commit')", $view);
        $this->assertStringContainsString('@if($preview !== [])', $view);
    }
}
