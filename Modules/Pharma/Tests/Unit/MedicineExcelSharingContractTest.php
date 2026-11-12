<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineExcelSharingContractTest extends TestCase
{
    #[Test]
    public function sharing_is_opt_in_and_is_scoped_to_admin_owned_templates(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_12_120000_add_sharing_to_medicine_excel_profiles.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/MedicineExcelProfile.php'));

        $this->assertStringContainsString("->boolean('is_shared')->default(false)", $migration);
        $this->assertStringContainsString("'is_shared' => 'boolean'", $model);
        $this->assertStringContainsString("if (\$guard === 'web')", $service);
        $this->assertStringContainsString("->where('owner_guard', 'admin')->where('is_shared', true)", $service);
        $this->assertStringContainsString("->where('owner_guard', 'admin')", $service);
        $this->assertStringContainsString("->where('user_id', \$adminId)", $service);
        $this->assertStringContainsString("->findOrFail(\$profileId)", $service);
    }

    #[Test]
    public function shared_template_selection_and_export_use_the_same_scoped_resolver(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));
        $catalog = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $export = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString("->listForUser((int) \$user->id, 'web')", $catalog);
        $this->assertStringContainsString("->forUser((int) \$user->id, \$data['profile_id'] ?? null, 'web')", $export);
        $this->assertStringContainsString("->findOrFail(\$id)", $service);
        $this->assertStringContainsString("where('owner_guard', \$guard)", $service);
        $this->assertStringContainsString("Dùng chung", $view);
    }

    #[Test]
    public function only_authorized_admin_can_toggle_sharing_from_designer(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        $this->assertStringContainsString("function toggleSharing(", $component);
        $this->assertStringContainsString("\$this->ownerGuard === 'admin' && auth('admin')->user()?->can('edit_pharma')", $component);
        $this->assertStringContainsString("setSharedByAdmin(\$adminId, \$this->profileId", $component);
        $this->assertStringContainsString('wire:click="toggleSharing"', $view);
        $this->assertStringContainsString("Chia sẻ cho User Web", $view);
        $this->assertStringContainsString("Thu hồi", $view);
    }
}
