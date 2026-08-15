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
}
