<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineHsspBoundaryContractTest extends TestCase
{
    #[Test]
    public function medicine_master_and_hssp_have_separate_route_ownership(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString("Route::prefix('medicines')->name('medicines.')", $routes);
        $this->assertStringContainsString("Route::get('/', [PharmaController::class, 'index'])", $routes);
        $this->assertStringContainsString("Route::prefix('hssp')->name('hssp.')", $routes);
        $this->assertStringContainsString("Route::get('/', [HsspController::class, 'index'])", $routes);
        $this->assertStringContainsString("Route::get('/{medicine}/create', [HsspController::class, 'create'])", $routes);
    }

    #[Test]
    public function hssp_has_dedicated_profile_persistence(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_14_110000_create_medicine_profiles_table.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/MedicineProfile.php'));
        $medicine = file_get_contents(base_path('Modules/Pharma/Models/Medicine.php'));

        $this->assertStringContainsString("Schema::create('pharma_medicine_profiles'", $migration);
        $this->assertStringContainsString("\$table->foreignId('medicine_id')", $migration);
        $this->assertStringContainsString("protected \$table = 'pharma_medicine_profiles'", $model);
        $this->assertStringContainsString('public function profiles(): HasMany', $medicine);
        $this->assertStringContainsString('public function currentProfile(): HasOne', $medicine);
    }

    #[Test]
    public function medicine_workspace_exposes_hssp_state_without_becoming_hssp(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Form.php'));

        $this->assertStringContainsString("public string \$filterHssp = ''", $component);
        $this->assertStringContainsString('Có HSSP', $view);
        $this->assertStringContainsString('Chưa có HSSP', $view);
        $this->assertStringContainsString("route('admin.pharma.hssp.create'", $view);
        $this->assertStringContainsString("redirect()->route('admin.pharma.medicines.index')", $form);
        $this->assertStringNotContainsString("redirect()->route('admin.pharma.hssp.index')", $form);
    }

    #[Test]
    public function dashboard_has_distinct_medicine_master_and_hssp_entries(): void
    {
        $dashboard = file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString("route('admin.pharma.medicines.index')", $dashboard);
        $this->assertStringContainsString("'admin.pharma.hssp.index'", $dashboard);
        $this->assertStringContainsString('Danh mục chuẩn', $dashboard);
        $this->assertStringContainsString('HSSP thuốc', $dashboard);
    }
}
