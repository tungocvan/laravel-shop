<?php

namespace Tests\Feature\Request\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\RequestOperationsQuery;
use Modules\Request\Application\Services\RetryRequestOperation;
use Modules\Request\Application\Services\ValidateTypeDraft;
use Modules\Request\Database\Seeders\RequestStarterTemplateSeeder;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Domain\Forms\FormPayloadValidator;
use Modules\Request\Jobs\GenerateRequestExport;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Tests\TestCase;

class RequestOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }

    public function test_operations_query_is_bounded(): void
    {
        RequestExportJob::factory()->count(3)->create(['status' => ExportStatus::Failed]);
        config(['request.operations.max_page_size' => 1]);

        $this->assertCount(1, app(RequestOperationsQuery::class)->failures(100));
    }

    public function test_retry_rejects_non_allowlisted_operation(): void
    {
        $this->expectException(ValidationException::class);

        app(RetryRequestOperation::class)->handle('artisan_command', '01ARZ3NDEKTSV4RRFFQ69G5FAV', 1, 'operation-test-key');
    }

    public function test_failed_export_retry_is_idempotent_and_queued_once(): void
    {
        Queue::fake();
        $actorId = $this->user('Operations Admin');
        $export = RequestExportJob::factory()->create([
            'status' => ExportStatus::Failed,
            'last_error_code' => 'REQUEST_EXPORT_GENERATION_FAILED',
        ]);

        $service = app(RetryRequestOperation::class);
        $service->handle('export_generation', $export->public_id, $actorId, 'same-operation-retry-key');
        $service->handle('export_generation', $export->public_id, $actorId, 'same-operation-retry-key');

        $this->assertSame(ExportStatus::Pending, $export->refresh()->status);
        Queue::assertPushed(GenerateRequestExport::class, 1);
        $this->assertSame(1, DB::table('request_idempotency_keys')->where('actor_id', $actorId)->where('command_key', 'request.operation.retry.export_generation')->count());
    }

    public function test_starter_template_is_opt_in_and_creates_draft_only(): void
    {
        $actorId = $this->user('Starter Admin', 'starter-admin@example.test');
        $approverId = $this->user('Starter Approver', 'starter-approver@example.test');

        config([
            'request.settings.starter_templates_enabled' => false,
            'request.settings.starter_template_actor_id' => $actorId,
            'request.settings.starter_template_approver_id' => $approverId,
        ]);
        app(RequestStarterTemplateSeeder::class)->run();
        $this->assertDatabaseMissing('request_groups', ['code' => 'STARTER']);

        config(['request.settings.starter_templates_enabled' => true]);
        app(RequestStarterTemplateSeeder::class)->run();
        app(RequestStarterTemplateSeeder::class)->run();

        $this->assertSame(1, RequestGroup::query()->where('code', 'STARTER')->count());
        $this->assertSame(1, RequestType::query()->where('code', 'GENERAL_APPROVAL')->count());
        $type = RequestType::query()->where('code', 'GENERAL_APPROVAL')->firstOrFail();
        $this->assertNotNull($type->active_draft_version_id);
        $this->assertNull($type->current_published_version_id);
        $this->assertDatabaseHas('request_type_versions', ['id' => $type->active_draft_version_id, 'status' => 'draft']);

        $advanceType = RequestType::query()->where('code', 'EXPENSE_REIMBURSEMENT')->firstOrFail();
        $this->assertSame('Đề xuất tạm ứng chi phí', $advanceType->name);
        $this->assertSame('Phòng Kinh doanh', $advanceType->group->name);
        $advanceDraft = $advanceType->activeDraft()->with(['audiences', 'stages'])->firstOrFail();
        $this->assertSame([
            'Thông tin đề xuất',
            'Kế hoạch và dự toán chi phí',
            'Nhận tiền và hoàn ứng',
        ], collect($advanceDraft->form_schema_json['sections'])->pluck('label')->all());
        $fields = collect($advanceDraft->form_schema_json['sections'])
            ->flatMap(fn (array $section): array => $section['fields'])
            ->keyBy('key');
        $this->assertSame('attachment', $fields['supporting_documents']['type']);
        $this->assertSame(5, $fields['supporting_documents']['validation']['max_count']);
        $this->assertSame('confidential', $fields['supporting_documents']['classification']);
        $this->assertFalse($fields['supporting_documents']['offline_draft']);
        $this->assertSame('Phê duyệt đề xuất tạm ứng', $advanceDraft->stages->firstOrFail()->name);
        $this->assertSame(1440, $advanceDraft->stages->firstOrFail()->sla_minutes);
        $this->assertSame(240, $advanceDraft->stages->firstOrFail()->warning_minutes_before);
        $this->assertSame([], app(ValidateTypeDraft::class)->handle($advanceDraft));

        $validCashProposal = [
            'proposal_title' => 'Tạm ứng chi phí tiếp khách khách hàng ABC',
            'expense_category' => 'customer_entertainment',
            'purpose' => 'Trao đổi kế hoạch hợp tác và mục tiêu doanh số quý IV.',
            'sales_team' => 'Nhóm Kinh doanh miền Nam',
            'needed_on' => '2026-09-01',
            'expense_from' => '2026-09-02',
            'expense_to' => '2026-09-02',
            'advance_amount_vnd' => '15000000',
            'budget_status' => 'planned',
            'cost_breakdown' => 'Tiếp khách: 10 người x 1.500.000 VND.',
            'advance_recipient' => 'Nguyễn Văn A',
            'payment_method' => 'cash',
            'previous_advance_status' => 'none',
            'settlement_due_on' => '2026-09-09',
        ];
        $payloads = app(FormPayloadValidator::class);
        $this->assertSame([], $payloads->validate($advanceDraft->form_schema_json, $validCashProposal, true)['errors']);
        $bankProposal = array_replace($validCashProposal, ['payment_method' => 'bank_transfer']);
        $this->assertSame(
            ['required'],
            $payloads->validate($advanceDraft->form_schema_json, $bankProposal, true)['errors']['payload.bank_information'],
        );
    }

    private function user(string $name, ?string $email = null): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email ?? uniqid('operations-', true).'@example.test',
            'is_active' => true,
            'password' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
