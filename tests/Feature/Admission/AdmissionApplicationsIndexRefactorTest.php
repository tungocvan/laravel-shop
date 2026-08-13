<?php

namespace Tests\Feature\Admission;

use Maatwebsite\Excel\Concerns\FromQuery;
use Modules\Admission\Exports\ApplicationsExport;
use Modules\Admission\Models\AdmissionApplication;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdmissionApplicationsIndexRefactorTest extends TestCase
{
    public function test_review_metadata_is_mass_assignable_and_timestamp_casted(): void
    {
        $model = new AdmissionApplication();

        foreach (['approved_at', 'approved_by', 'rejected_at', 'rejected_by'] as $field) {
            $this->assertContains($field, $model->getFillable());
        }

        $casts = $model->getCasts();
        $this->assertSame('datetime', $casts['approved_at']);
        $this->assertSame('datetime', $casts['rejected_at']);
    }

    public function test_export_uses_query_based_excel_contract(): void
    {
        $this->assertContains(FromQuery::class, class_implements(ApplicationsExport::class));
    }

    #[DataProvider('sensitiveActionsProvider')]
    public function test_livewire_sensitive_actions_use_expected_admin_permission(string $action, string $permission): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Livewire/Admin/Applications/Index.php'));

        $this->assertStringContainsString("function {$action}", $source);
        $this->assertStringContainsString("'{$permission}'", $source);
        $this->assertStringContainsString("Auth::guard('admin')->user()", $source);
    }

    public static function sensitiveActionsProvider(): array
    {
        return [
            ['approve', 'approve_admission'],
            ['reject', 'reject_admission'],
            ['deleteSelected', 'delete_admission'],
            ['deleteAll', 'delete_admission'],
            ['delete', 'delete_admission'],
            ['generateSelectedDocuments', 'download_admission_documents'],
            ['generateDocuments', 'download_admission_documents'],
            ['export', 'export_admission'],
        ];
    }

    public function test_livewire_delegates_application_workflows_to_admin_service(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Livewire/Admin/Applications/Index.php'));

        $this->assertStringContainsString('AdmissionApplicationAdminService', $source);
        $this->assertStringContainsString('deleteAllAndResetIncrement', $source);
        $this->assertStringContainsString('queueDocumentsForIds', $source);
        $this->assertStringContainsString('queueDocumentsForFilters', $source);
        $this->assertStringContainsString('Bus::findBatch', $source);
        $this->assertStringNotContainsString('AdmissionApplication::', $source);
        $this->assertStringNotContainsString("'all'", $source);
    }

    public function test_delete_all_service_preserves_model_hooks_and_resets_mysql_auto_increment(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Services/AdmissionApplicationAdminService.php'));

        $this->assertStringContainsString('function deleteAllAndResetIncrement', $source);
        $this->assertStringContainsString('$application->delete()', $source);
        $this->assertStringContainsString('ALTER TABLE `admission_applications` AUTO_INCREMENT = 1', $source);
        $this->assertStringNotContainsString('truncate()', $source);
    }

    public function test_bulk_documents_use_batch_queue_and_selectable_formats(): void
    {
        $service = file_get_contents(base_path('Modules/Admission/Services/AdmissionApplicationAdminService.php'));
        $job = file_get_contents(base_path('Modules/Admission/Jobs/GenerateAdmissionPdfJob.php'));
        $config = file_get_contents(base_path('Modules/Admission/config/module.php'));
        $migration = file_get_contents(base_path('Modules/Admission/database/migrations/2026_08_13_000003_create_job_batches_table_if_missing.php'));

        $this->assertStringContainsString('Bus::batch($jobs)', $service);
        $this->assertStringContainsString("->onQueue('admission-documents')", $service);
        $this->assertStringContainsString('bool $docx = true', $service);
        $this->assertStringContainsString('bool $pdf = false', $service);
        $this->assertStringContainsString('use Batchable', $job);
        $this->assertStringContainsString('$this->onQueue(\'admission-documents\')', $job);
        $this->assertStringContainsString('public bool $generateDocx = true', $job);
        $this->assertStringContainsString('public ?bool $generatePdf = null', $job);
        $this->assertStringContainsString("config('admission.module.enable_pdf_convert', false)", $job);
        $this->assertStringContainsString("env('ENABLE_PDF_CONVERT', false)", $config);
        $this->assertStringContainsString("Schema::hasTable('job_batches')", $migration);
        $this->assertStringContainsString("'Don_' . \$this->id", $job);
    }

    public function test_blade_uses_capability_specific_gates_and_bounded_page_sizes(): void
    {
        $blade = file_get_contents(base_path('Modules/Admission/resources/views/livewire/admin/applications/index.blade.php'));

        foreach ([
            'export_admission',
            'import_admission',
            'approve_admission',
            'reject_admission',
            'delete_admission',
            'edit_admission',
            'download_admission_documents',
        ] as $permission) {
            $this->assertStringContainsString($permission, $blade);
        }

        $this->assertStringNotContainsString('<option value="all">', $blade);
        $this->assertStringContainsString('wire:confirm=', $blade);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $blade);
        $this->assertStringContainsString('wire:click="deleteSelected"', $blade);
        $this->assertStringContainsString('wire:click="deleteAll"', $blade);
        $this->assertStringContainsString('wire:click="generateSelectedDocuments"', $blade);
        $this->assertStringContainsString('wire:click="generateDocuments"', $blade);
        $this->assertStringContainsString('wire:model.live="generateDocx"', $blade);
        $this->assertStringContainsString('wire:model.live="generatePdf"', $blade);
        $this->assertStringContainsString('Tiến độ tạo tài liệu', $blade);
        $this->assertStringContainsString('wire:poll.2s="$refresh"', $blade);
        $this->assertStringContainsString('Tạo file đã chọn', $blade);
        $this->assertStringContainsString('Tạo file còn thiếu', $blade);
        $this->assertStringContainsString('name="restore_status"', $blade);
        $this->assertStringContainsString('Xóa đã chọn', $blade);
        $this->assertStringContainsString('Xóa tất cả', $blade);
        $this->assertStringContainsString('Không có hồ sơ phù hợp với bộ lọc hiện tại.', $blade);
    }
}
