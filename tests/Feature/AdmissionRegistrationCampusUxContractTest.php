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
    public function dvhc_row_edit_does_not_send_a_live_request_before_blur_update(): void
    {
        $view = file_get_contents(base_path('Modules/Admission/resources/views/livewire/dvhc.blade.php'));

        $this->assertStringContainsString('wire:model="rows.{{ $index }}.province_name"', $view);
        $this->assertStringContainsString('wire:model="rows.{{ $index }}.ward_name"', $view);
        $this->assertStringNotContainsString('wire:model.live="rows.{{ $index }}.province_name"', $view);
        $this->assertStringNotContainsString('wire:model.live="rows.{{ $index }}.ward_name"', $view);
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
}
