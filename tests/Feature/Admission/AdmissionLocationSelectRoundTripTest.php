<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionRegistrationService;
use Tests\TestCase;

class AdmissionLocationSelectRoundTripTest extends TestCase
{
    public function test_imported_location_values_are_mapped_to_edit_form_without_loss(): void
    {
        $values = [
            'noi_sinh_tt' => 'Thành phố Hồ Chí Minh',
            'noi_sinh_px' => 'Phường Bến Thành',
            'noi_dang_ky_khai_sinh_tt' => 'Thành phố Hồ Chí Minh',
            'noi_dang_ky_khai_sinh_px' => 'Phường Tân Hưng',
            'que_quan_tt' => 'Tỉnh Thanh Hóa',
            'que_quan_px' => 'Phường Đông Tiến',
            'ttttp' => 'Thành phố Hồ Chí Minh',
            'ttpx' => 'Phường Bến Thành',
            'htttp' => 'Tỉnh Thanh Hóa',
            'htpx' => 'Phường Đông Tiến',
        ];
        $model = new AdmissionApplication;
        $model->forceFill($values);

        $form = app(AdmissionRegistrationService::class)->toForm($model);
        foreach ([
            'NoiSinhTt' => 'noi_sinh_tt', 'NoiSinhPx' => 'noi_sinh_px',
            'NoiDangKyKhaiSinhTt' => 'noi_dang_ky_khai_sinh_tt',
            'NoiDangKyKhaiSinhPx' => 'noi_dang_ky_khai_sinh_px',
            'QueQuanTt' => 'que_quan_tt', 'QueQuanPx' => 'que_quan_px',
            'TTTTP' => 'ttttp', 'TTPX' => 'ttpx',
            'HTTTP' => 'htttp', 'HTPX' => 'htpx',
        ] as $key => $column) {
            $this->assertSame($values[$column], $form[$key]);
        }
    }

    public function test_admission_location_controls_are_livewire_native_and_preserve_legacy_values(): void
    {
        $partial = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/location-select.blade.php'));
        $stepOne = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/step-1-student.blade.php'));
        $stepTwo = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admission/partials/step-2-address.blade.php'));
        $component = file_get_contents(base_path('Modules/Admission/Livewire/Public/RegistrationForm.php'));

        $this->assertStringContainsString('wire:model.live="form.{{ $field }}"', $partial);
        $this->assertStringContainsString('chưa có trong danh mục', $partial);
        $this->assertStringNotContainsString('wire:ignore', $partial);
        $this->assertSame(6, substr_count($stepOne, "partials.location-select"));
        $this->assertSame(4, substr_count($stepTwo, "partials.location-select"));
        $this->assertStringContainsString("'form.NoiSinhTt' => 'NoiSinhPx'", $component);
        $this->assertStringContainsString("'form.HTTTP' => 'HTPX'", $component);
    }
}
