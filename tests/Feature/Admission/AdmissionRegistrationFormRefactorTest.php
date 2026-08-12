<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionRegistrationService;
use Tests\TestCase;

class AdmissionRegistrationFormRefactorTest extends TestCase
{
    public function test_registration_form_enforces_admin_create_and_edit_permissions(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));

        $this->assertStringContainsString("Auth::guard('admin')->user()", $source);
        $this->assertStringContainsString("'create_admission'", $source);
        $this->assertStringContainsString("'edit_admission'", $source);
        $this->assertStringContainsString('$this->authorizeAdmin(\'edit_admission\')', $source);
    }

    public function test_registration_form_does_not_own_approval_transition(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));

        $this->assertStringNotContainsString("'Status' => 'approved'", $source);
        $this->assertStringNotContainsString('$data[\'Status\'] = \'approved\'', $source);
        $this->assertStringNotContainsString('approve_admission', $source);
    }

    public function test_registration_service_forces_pending_status_on_create(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Services/AdmissionRegistrationService.php'));

        $this->assertStringContainsString('$form[\'Status\'] = \'pending\'', $source);
        $this->assertStringContainsString('unset($form[\'Status\'])', $source);
    }

    public function test_db_to_form_mapping_preserves_known_round_trip_fields(): void
    {
        $application = new AdmissionApplication([
            'chuc_vu_cha' => 'Trưởng phòng',
            'chuc_vu_me' => 'Kế toán',
            'quan_he_giam_ho' => 'Cô',
            'ngay_lam_don' => '2026-08-01',
            'noi_sinh_chi_tiet' => 'Bệnh viện A',
            'ck_goc_hoc_tap' => false,
            'ck_sach_vo' => false,
            'ck_hop_ph' => false,
            'ck_tham_gia_hd' => false,
            'ck_gan_gui' => false,
        ]);

        $form = app(AdmissionRegistrationService::class)->toForm($application);

        $this->assertSame('Trưởng phòng', $form['ChucVuCha']);
        $this->assertSame('Kế toán', $form['ChucVuMe']);
        $this->assertSame('Cô', $form['QuanHeGiamHo']);
        $this->assertSame('2026-08-01', $form['NgayLamDon']);
        $this->assertSame('Bệnh viện A', $form['NoiSinhChiTiet']);
        $this->assertFalse($form['CK_GocHocTap']);
        $this->assertFalse($form['CK_SachVo']);
        $this->assertFalse($form['CK_HopPH']);
        $this->assertFalse($form['CK_ThamGiaHD']);
        $this->assertFalse($form['CK_GanGui']);
    }

    public function test_registration_form_has_step_validation_and_bounds(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));

        $this->assertStringContainsString('validateCurrentStep', $source);
        $this->assertStringContainsString('max(1, min($this->totalSteps', $source);
        $this->assertStringContainsString("'form.MaDinhDanh' => ['required', 'digits:12']", $source);
        $this->assertStringContainsString('Rule::in($this->registrationClasses)', $source);
    }

    public function test_registration_blade_has_loading_error_and_correct_edit_capability_contracts(): void
    {
        $actions = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/actions.blade.php'));
        $errors = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/error-summary.blade.php'));
        $stepFive = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/step-5-confirm.blade.php'));
        $adminPage = file_get_contents(base_path('Modules/Admission/resources/views/pages/admin/create.blade.php'));

        $this->assertStringContainsString('wire:loading.attr="disabled"', $actions);
        $this->assertStringContainsString('Đang lưu hồ sơ...', $actions);
        $this->assertStringContainsString("session('error')", $errors);
        $this->assertStringContainsString("@can('edit_admission')", $stepFive);
        $this->assertStringNotContainsString("@can('delete_admission')", $stepFive);
        $this->assertStringContainsString("@can('create_admission')", $adminPage);
    }
}
