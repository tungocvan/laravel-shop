<?php

namespace Tests\Feature\Administrative;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Livewire\Procedures\ProcedureTable;
use Modules\Administrative\Livewire\Submissions\SubmissionTable;
use Modules\Administrative\Services\ProcedureService;
use Modules\Administrative\Services\SubmissionService;
use ReflectionClass;
use Tests\TestCase;

class AdministrativeRefactorContractTest extends TestCase
{
    public function test_admin_file_service_imports_the_administrative_file_model(): void
    {
        $source = file_get_contents(base_path('Modules/Administrative/Services/AdministrativeFileService.php'));
        $this->assertStringContainsString('use Modules\\Administrative\\Models\\AdministrativeFile;', $source);
        $this->assertStringContainsString('AdministrativeFile::query()', $source);
    }

    public function test_admin_tables_only_offer_bounded_page_sizes(): void
    {
        $procedure = new ProcedureTable;
        $submission = new SubmissionTable;
        $this->assertSame([10, 25, 50, 100], $procedure->perPageOptions);
        $this->assertSame([10, 25, 50, 100], $submission->perPageOptions);
        $this->assertIsInt($procedure->perPage);
        $this->assertIsInt($submission->perPage);
    }

    public function test_admin_list_services_always_return_paginators_and_normalize_page_size(): void
    {
        $procedureMethod = (new ReflectionClass(ProcedureService::class))->getMethod('listForAdmin');
        $submissionMethod = (new ReflectionClass(SubmissionService::class))->getMethod('listForAdmin');
        $this->assertSame(LengthAwarePaginator::class, (string) $procedureMethod->getReturnType());
        $this->assertSame(LengthAwarePaginator::class, (string) $submissionMethod->getReturnType());

        $procedureSource = file_get_contents(base_path('Modules/Administrative/Services/ProcedureService.php'));
        $submissionSource = file_get_contents(base_path('Modules/Administrative/Services/SubmissionService.php'));
        $allBranch = '$perPage'." === 'All'";
        $this->assertStringNotContainsString($allBranch, $procedureSource);
        $this->assertStringNotContainsString($allBranch, $submissionSource);
        $this->assertStringContainsString('normalizeAdminPageSize($perPage)', $procedureSource);
        $this->assertStringContainsString('normalizeAdminPageSize($perPage)', $submissionSource);
    }

    public function test_archive_action_is_part_of_the_history_contract(): void
    {
        $this->assertSame('archived', SubmissionAction::Archived->value);
        $source = file_get_contents(base_path('Modules/Administrative/Services/SubmissionService.php'));
        $this->assertStringContainsString('SubmissionAction::Archived', $source);
        $this->assertStringContainsString("'soft_delete' => true", $source);
    }

    public function test_processing_actions_keep_process_as_canonical_with_edit_fallback(): void
    {
        $source = file_get_contents(base_path('Modules/Administrative/Livewire/Submissions/SubmissionDetail.php'));
        $permissionPair = "['administrative.submission.process', 'administrative.submission.edit']";
        $this->assertSame(3, substr_count($source, 'authorizeAnyPermission('.$permissionPair.')'));
        $this->assertStringContainsString('Gate::forUser($user)->any($permissions)', $source);
    }

    public function test_delete_all_is_permission_protected_and_audited(): void
    {
        $component = file_get_contents(base_path('Modules/Administrative/Livewire/Submissions/SubmissionTable.php'));
        $service = file_get_contents(base_path('Modules/Administrative/Services/SubmissionService.php'));
        $this->assertStringContainsString('public function requestDeleteAll()', $component);
        $this->assertStringContainsString("authorizePermission('administrative.submission.delete')", $component);
        $this->assertStringContainsString('public function softDeleteAll(int $adminId): int', $service);
        $this->assertStringContainsString("'source' => 'delete_all'", $service);
        $this->assertStringContainsString('SubmissionAction::Archived', $service);
    }

    public function test_delete_selected_uses_modal_confirmation(): void
    {
        $view = file_get_contents(base_path('Modules/Administrative/resources/views/livewire/submissions/submission-table.blade.php'));
        $modal = file_get_contents(base_path('Modules/Administrative/resources/views/components/delete-selected-modal.blade.php'));

        $this->assertStringContainsString('<x-Administrative::delete-selected-modal', $view);
        $this->assertStringContainsString('fixed inset-0 z-50', $modal);
        $this->assertStringContainsString('wire:click="deleteSelected"', $modal);
    }

    public function test_administrative_tables_use_custom_indigo_pagination(): void
    {
        $submissionView = file_get_contents(base_path('Modules/Administrative/resources/views/livewire/submissions/submission-table.blade.php'));
        $procedureView = file_get_contents(base_path('Modules/Administrative/resources/views/livewire/procedures/procedure-table.blade.php'));
        $paginationView = file_get_contents(base_path('Modules/Administrative/resources/views/components/pagination.blade.php'));
        $this->assertStringContainsString("links('Administrative::components.pagination')", $submissionView);
        $this->assertStringContainsString("links('Administrative::components.pagination')", $procedureView);
        $this->assertStringContainsString('bg-indigo-600', $paginationView);
        $this->assertStringNotContainsString('bg-gray-800', $paginationView);
    }

    public function test_administrative_import_export_uses_shared_foundation_and_permission(): void
    {
        $service = file_get_contents(base_path('Modules/Administrative/Services/ImportExport.php'));
        $view = file_get_contents(base_path('Modules/Administrative/resources/views/livewire/submissions/submission-table.blade.php'));
        $panel = file_get_contents(base_path('Modules/Shared/Livewire/ImportExport/Panel.php'));
        $panelView = file_get_contents(base_path('Modules/Shared/Resources/views/livewire/import-export/panel.blade.php'));
        $task = file_get_contents(base_path('.codex/tasks/create-import-export.md'));
        $module = require base_path('Modules/Administrative/config/module.php');
        $unsafeLookupExport = "'lookup_token_hash' => ".'$model';
        $selectedIdsBinding = "'selected_ids' => ".'$selectedIds';

        $this->assertStringContainsString('extends BaseImportExportService', $service);
        $this->assertStringContainsString("'lookup_token' => ['nullable'", $service);
        $this->assertStringContainsString('AdministrativeSubmission::withTrashed()', $service);
        $this->assertStringContainsString($selectedIdsBinding, $view);
        $this->assertStringContainsString('#[Reactive]', $panel);
        $this->assertStringContainsString('showSuccessModal', $panel);
        $this->assertStringContainsString('acknowledgeSuccess', $panel);
        $this->assertStringContainsString('OK — tải lại', $panelView);
        $this->assertStringContainsString('selected_ids empty', $task);
        $this->assertStringContainsString('selected_ids not empty', $task);
        $this->assertStringContainsString('Hash::make($lookupToken)', $service);
        $this->assertStringContainsString("'[REDACTED]'", $service);
        $this->assertStringContainsString("'source' => 'administrative_import'", $service);
        $this->assertStringContainsString('Mode replace bị vô hiệu', $service);
        $this->assertStringContainsString("@livewire('shared.import-export.panel'", $view);
        $this->assertStringContainsString("'permission' => 'administrative.submission.import_export'", $view);
        $this->assertContains('administrative.submission.import_export', $module['permissions']);
        $this->assertStringNotContainsString($unsafeLookupExport, $service);
    }
}
