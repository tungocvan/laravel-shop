<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Services\SchoolSettingService;
use Tests\TestCase;

class AdmissionCodePrefixSettingsTest extends TestCase
{
    public function test_prefix_is_configurable_with_backward_compatible_default(): void
    {
        $this->assertSame('NVH', SchoolSettingService::DEFAULTS['application_code_prefix']);

        $form = file_get_contents(base_path('Modules/Admission/Livewire/Admin/SchoolSettingsForm.php'));
        $this->assertStringContainsString("'application_code_prefix' => ['required', 'string', 'size:3'", $form);
        $this->assertStringContainsString('strtoupper(trim($this->application_code_prefix))', $form);

        $registration = file_get_contents(base_path('Modules/Admission/Services/AdmissionService.php'));
        $import = file_get_contents(base_path('Modules/Admission/Imports/ApplicationsImport.php'));
        $this->assertStringContainsString("get('application_code_prefix')", $registration);
        $this->assertStringContainsString("get('application_code_prefix')", $import);
    }
}
