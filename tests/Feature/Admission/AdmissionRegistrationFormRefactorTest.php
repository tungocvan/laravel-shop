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
            'so_so_tiem_chung' => 'SSTC-2026-001',
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
        $this->assertSame('SSTC-2026-001', $form['SoSoTiemChung']);
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

    public function test_vaccination_book_number_is_wired_through_form_model_and_migration(): void
    {
        $formSource = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));
        $serviceSource = file_get_contents(base_path('Modules/Admission/Services/AdmissionService.php'));
        $stepFive = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/step-5-confirm.blade.php'));
        $migration = file_get_contents(base_path('Modules/Admission/database/migrations/2026_10_07_160000_add_vaccination_book_number_to_admission_applications_table.php'));

        $this->assertContains('so_so_tiem_chung', (new AdmissionApplication)->getFillable());
        $this->assertStringContainsString("'SoSoTiemChung' => ''", $formSource);
        $this->assertStringContainsString("'form.SoSoTiemChung' => ['nullable', 'string', 'max:255']", $formSource);
        $this->assertStringContainsString("'so_so_tiem_chung' => trim((string) (\$formData['SoSoTiemChung'] ?? '')) ?: null", $serviceSource);
        $this->assertStringContainsString('wire:model="form.SoSoTiemChung"', $stepFive);
        $this->assertLessThan(
            strpos($stepFive, 'Sắp xếp vào lớp'),
            strpos($stepFive, 'Số sổ tiêm chủng')
        );
        $this->assertStringContainsString("text('so_so_tiem_chung')->nullable()->after('nguoi_lam_don')", $migration);
    }

    public function test_vaccination_only_edit_preserves_review_status_contract(): void
    {
        $modelSource = file_get_contents(base_path('Modules/Admission/Models/AdmissionApplication.php'));

        $this->assertStringContainsString("\$statusPreservingFields = ['so_so_tiem_chung']", $modelSource);
        $this->assertStringContainsString('$onlyStatusPreservingFieldsChanged', $modelSource);
        $this->assertStringContainsString('! $onlyStatusPreservingFieldsChanged', $modelSource);
    }

    public function test_receipt_qr_uses_signed_prefill_without_credentials_in_url(): void
    {
        $service = file_get_contents(base_path('Modules/Admission/Services/AdmissionService.php'));
        $routes = file_get_contents(base_path('Modules/Admission/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Admission/Http/Controllers/AdmissionController.php'));
        $search = file_get_contents(base_path('Modules/Admission/Livewire/Search.php'));
        $page = file_get_contents(base_path('Modules/Admission/resources/views/pages/public/search.blade.php'));

        $method = substr($service, strpos($service, 'public function generateBienNhan'));

        $this->assertStringContainsString("URL::signedRoute('admission.search.receipt'", $method);
        $this->assertStringContainsString("'application' => \$app->id", $method);
        $this->assertStringNotContainsString("'password' =>", $method);
        $this->assertStringNotContainsString("'ma_dinh_danh' =>", $method);

        $this->assertStringContainsString("->middleware('signed')", $routes);
        $this->assertStringContainsString("->name('search.receipt')", $routes);
        $this->assertStringContainsString("public function receiptSearch(AdmissionApplication \$application)", $controller);
        $this->assertStringContainsString("abort_unless(\$application->status === 'approved', 404)", $controller);
        $this->assertStringContainsString("'ma_dinh_danh' => (string) \$application->ma_dinh_danh", $controller);
        $this->assertStringContainsString("format('dmY')", $controller);
        $this->assertStringContainsString("public function mount(string \$maDinhDanh = '', string \$password = '')", $search);
        $this->assertStringContainsString(':ma-dinh-danh="$receiptPrefill[\'ma_dinh_danh\'] ?? \'\'"', $page);
        $this->assertStringContainsString(':password="$receiptPrefill[\'password\'] ?? \'\'"', $page);
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
