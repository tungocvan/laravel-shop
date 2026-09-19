<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class MedicineHsspValidityContractTest extends TestCase
{
    public function test_medicine_edit_prefers_hssp_registration_and_gmp_expiry(): void
    {
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Form.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/HsspMedicineValidityService.php'));

        $this->assertIsString($form);
        $this->assertIsString($service);
        $this->assertStringContainsString('HsspMedicineValidityService $hsspValidity', $form);
        $this->assertStringContainsString("\$validity['visa_validity_date'] ?? \$this->visa_validity_date", $form);
        $this->assertStringContainsString("\$validity['gmp_certification_date'] ?? \$this->gmp_certification_date", $form);
        $this->assertStringContainsString("'visa_validity_date' => \$this->effectiveTo(\$dossier, 'registration')", $service);
        $this->assertStringContainsString("'gmp_certification_date' => \$this->effectiveTo(\$dossier, 'gmp')", $service);
        $this->assertStringContainsString("'effective_to'", $service);
        $this->assertStringContainsString("->where('owner_type', MedicineProfile::class)", $service);
    }
}
