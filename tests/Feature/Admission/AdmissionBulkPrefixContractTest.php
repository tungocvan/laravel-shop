<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Services\AdmissionCodePrefixService;
use Tests\TestCase;

class AdmissionBulkPrefixContractTest extends TestCase
{
    public function test_bulk_prefix_change_is_confirmed_and_transactional(): void
    {
        $service = file_get_contents(base_path('Modules/Admission/Services/AdmissionCodePrefixService.php'));
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admin/school-settings-form.blade.php'));
        $component = file_get_contents(base_path('Modules/Admission/Livewire/Admin/SchoolSettingsForm.php'));

        $this->assertTrue(class_exists(AdmissionCodePrefixService::class));
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("isset(\$newCodes[\$new])", $service);
        $this->assertStringContainsString("Cache::forget('admission.school-settings')", $service);
        $this->assertStringContainsString('wire:confirm=', $view);
        $this->assertStringContainsString('MHS20260001', $view);
        $this->assertStringContainsString("authorize('manage_admission_settings')", $component);
    }
}
