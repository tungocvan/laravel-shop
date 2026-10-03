<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmissionRegistrationCampusUxContractTest extends TestCase
{
    #[Test]
    public function registration_uses_vietnamese_validation_modal_and_configured_campuses(): void
    {
        $component = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/registration-form.blade.php'));
        $stepFive = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/step-5-confirm.blade.php'));

        $this->assertStringContainsString("show-validation-modal", $component);
        $this->assertStringContainsString("Hồ sơ còn thông tin thiếu hoặc chưa hợp lệ", $component);
        $this->assertStringContainsString("Về bước cần cập nhật", $view);
        $this->assertStringContainsString('wire:model.live="form.SchoolCampusName"', $stepFive);
        $this->assertStringContainsString('Địa chỉ được tự động điền theo cơ sở đã chọn', $stepFive);
    }

    #[Test]
    public function school_settings_define_the_three_default_campuses(): void
    {
        $service = file_get_contents(base_path('Modules/Admission/Services/SchoolSettingService.php'));

        $this->assertStringContainsString('203 Lâm Văn Bền, phường Tân Thuận, Thành phố Hồ Chí Minh', $service);
        $this->assertStringContainsString('215 Trần Xuân Soạn, phường Tân Thuận, Thành phố Hồ Chí Minh', $service);
        $this->assertStringContainsString('37/2 Huỳnh Tấn Phát, phường Tân Thuận, Thành phố Hồ Chí Minh', $service);
        $this->assertStringContainsString('school_campuses', $service);
    }

    #[Test]
    public function dvhc_row_save_uses_a_standard_laravel_post_instead_of_livewire(): void
    {
        $routes = file_get_contents(base_path('Modules/Admission/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Admission/Http/Controllers/AdmissionController.php'));
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/dvhc.blade.php'));

        $this->assertStringContainsString("Route::post('/dvhc/{location}'", $routes);
        $this->assertStringContainsString("->name('dvhc.update')", $routes);
        $this->assertStringContainsString('public function updateDvhc(Request $request, AdmissionLocation $location)', $controller);
        $this->assertStringContainsString("->with('success', 'Đã cập nhật tên tỉnh và phường/xã.')", $controller);
        $this->assertStringContainsString('method="POST" action="{{ route(\'admin.admission.dvhc.update\', $row[\'id\']) }}"', $view);
        $this->assertStringContainsString('@csrf', $view);
        $this->assertStringContainsString('name="province_name"', $view);
        $this->assertStringContainsString('name="ward_name"', $view);
        $this->assertStringNotContainsString('wire:click="updateRow(', $view);
    }

    #[Test]
    public function dvhc_bulk_delete_uses_standard_post_instead_of_livewire(): void
    {
        $routes = file_get_contents(base_path('Modules/Admission/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Admission/Http/Controllers/AdmissionController.php'));
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/dvhc.blade.php'));

        $this->assertStringContainsString("Route::post('/dvhc-delete-selected'", $routes);
        $this->assertStringContainsString('public function deleteDvhcSelected(Request $request)', $controller);
        $this->assertStringContainsString("route('admin.admission.dvhc.delete-selected')", $view);
        $this->assertStringContainsString('name="ids[]"', $view);
        $this->assertStringNotContainsString('wire:click="deleteSelected"', $view);
    }

    #[Test]
    public function edit_wizard_allows_direct_step_review_and_reports_exact_validation_errors(): void
    {
        $component = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));

        $this->assertStringContainsString('if ($this->isEdit)', $component);
        $this->assertStringContainsString("'Cần cập nhật: '.\$details", $component);
        $this->assertStringContainsString("collect(\$errors)->take(5)->implode(' • ')", $component);
    }

    #[Test]
    public function public_search_result_shows_assigned_campus_and_address(): void
    {
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/search.blade.php'));

        $this->assertStringContainsString('Cơ sở / Phân hiệu:', $view);
        $this->assertStringContainsString("school_campus_name", $view);
        $this->assertStringContainsString("school_campus_address", $view);
    }

    #[Test]
    public function campus_snapshot_is_persisted_on_the_application(): void
    {
        $model = file_get_contents(base_path('Modules/Admission/Models/AdmissionApplication.php'));
        $service = file_get_contents(base_path('Modules/Admission/Services/AdmissionService.php'));

        $this->assertStringContainsString("'school_campus_name'", $model);
        $this->assertStringContainsString("'school_campus_address'", $model);
        $this->assertStringContainsString("'school_campus_name' => \$formData['SchoolCampusName']", $service);
        $this->assertStringContainsString("'school_campus_address' => \$formData['SchoolCampusAddress']", $service);
    }
    #[Test]
    public function edit_wizard_navigation_is_client_side_while_create_keeps_server_validation(): void
    {
        $form = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/registration-form.blade.php'));
        $stepper = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/stepper.blade.php'));
        $actions = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/actions.blade.php'));

        $this->assertStringContainsString('x-data="{ uiStep: {{ (int) $currentStep }} }"', $form);
        $this->assertStringContainsString('x-show="uiStep === 5"', $form);
        $this->assertStringContainsString('x-on:click="uiStep = {{ $stepNumber }}"', $stepper);
        $this->assertStringContainsString('wire:click="setStep({{ $stepNumber }})"', $stepper);
        $this->assertStringContainsString('x-on:click="uiStep = Math.min(5, uiStep + 1)"', $actions);
        $this->assertStringContainsString('wire:click="nextStep"', $actions);
    }
}
