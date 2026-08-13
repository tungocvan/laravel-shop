<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Models\AdmissionImportError;
use Modules\Admission\Models\AdmissionImportRun;
use Tests\TestCase;

class AdmissionImportTrackingTest extends TestCase
{
    public function test_import_run_and_error_models_expose_expected_contracts(): void
    {
        $run = new AdmissionImportRun();
        $error = new AdmissionImportError();

        foreach ([
            'original_filename', 'status', 'total_rows', 'success_rows', 'failed_rows',
            'created_rows', 'updated_rows', 'imported_by', 'started_at', 'completed_at', 'fatal_error',
        ] as $field) {
            $this->assertContains($field, $run->getFillable());
        }

        foreach ([
            'import_run_id', 'row_number', 'error_code', 'field', 'error_message',
            'ma_dinh_danh', 'mhs', 'student_name', 'row_snapshot',
        ] as $field) {
            $this->assertContains($field, $error->getFillable());
        }

        $this->assertSame('array', $error->getCasts()['row_snapshot']);
    }

    public function test_admission_controller_uses_tracked_import_service_instead_of_generic_import(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Http/Controllers/AdmissionController.php'));

        $this->assertStringContainsString('AdmissionImportService', $source);
        $this->assertStringContainsString("'import_summary'", $source);
        $this->assertStringContainsString("'mimes:xlsx,xls'", $source);
        $this->assertStringNotContainsString('GenericImport', $source);
    }

    public function test_importer_skips_row_errors_and_uses_stable_error_codes(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Imports/ApplicationsImport.php'));

        foreach ([
            'missing_identity',
            'invalid_ma_dinh_danh',
            'invalid_date',
            'identity_conflict',
            'ambiguous_identity',
            'persistence_failed',
        ] as $code) {
            $this->assertStringContainsString($code, $source);
        }

        $this->assertStringContainsString('foreach ($rows as $index => $row)', $source);
        $this->assertStringContainsString('recordError(', $source);
        $this->assertStringContainsString('DB::transaction(function () use ($record, $data)', $source);
        $this->assertStringNotContainsString('DB::transaction(function () use ($rows)', $source);
    }

    public function test_importer_preserves_lifecycle_status_and_limits_error_snapshot(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Imports/ApplicationsImport.php'));

        $this->assertStringContainsString("'status'", $source);
        $this->assertStringContainsString('$data[\'status\'] = \'pending\'', $source);
        $this->assertStringContainsString("'row_snapshot'", $source);
        $this->assertStringContainsString("'ho_va_ten_hoc_sinh'", $source);

        foreach (['cccd_cha', 'cccd_me', 'dien_thoai_cha', 'dien_thoai_me', 'suc_khoe_can_luu_y'] as $sensitiveField) {
            $snapshotSection = substr($source, strpos($source, "'row_snapshot'"));
            $this->assertStringNotContainsString("'{$sensitiveField}'", substr($snapshotSection, 0, 1000));
        }
    }

    public function test_admin_index_exposes_import_summary_history_and_error_actions(): void
    {
        $blade = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admin/applications/index.blade.php'));

        $this->assertStringContainsString("session('import_summary')", $blade);
        $this->assertStringContainsString('Lịch sử Import', $blade);
        $this->assertStringContainsString('Xem {{ $summary[\'failed\'] }} lỗi Import', $blade);
        $this->assertStringContainsString("route('admin.admission.imports.errors'", $blade);
    }

    public function test_import_tracking_pages_are_present_and_paginated(): void
    {
        $history = file_get_contents(base_path('Modules/Admission/resources/views/pages/admin/imports/index.blade.php'));
        $errors = file_get_contents(base_path('Modules/Admission/resources/views/pages/admin/imports/errors.blade.php'));

        $this->assertStringContainsString('$runs->links()', $history);
        $this->assertStringContainsString('$errors->links()', $errors);
        $this->assertStringContainsString('Dòng Excel', $errors);
        $this->assertStringContainsString('error_code', $errors);
    }
}
