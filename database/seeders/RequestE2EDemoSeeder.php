<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Request\Application\Services\AddRequestComment;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Application\Services\EnforceRequestTaskSla;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Database\Seeders\RequestStarterTemplateSeeder;
use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RequestE2EDemoSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('RequestE2EDemoSeeder chỉ được phép chạy ngoài production.');
        }

        $now = now('UTC');
        $superAdmin = User::query()->where('email', 'tungocvan@gmail.com')->firstOrFail();
        $users = [
            'employee' => $this->upsertUser('tungocvan1@gmail.com', 'Từ Ngọc Vân · Nhân viên E2E', $now),
            'approver' => $this->upsertUser('vhdtshop@gmail.com', 'VHDT Shop · Người duyệt E2E', $now),
            'finance' => $this->upsertUser('vansala78@gmail.com', 'Vân Sala · Tài chính E2E', $now),
            'auditor' => $this->upsertUser('hamadaqc01@gmail.com', 'Hamada QC · Kiểm toán E2E', $now),
        ];

        $guard = (string) (Permission::query()->where('name', 'request.dashboard.view')->value('guard_name') ?? 'admin');
        $roles = [
            'requester' => $this->syncRole('Request E2E · Nhân viên', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-own', 'request.instance.create', 'request.instance.update-own', 'request.instance.submit', 'request.instance.cancel-own', 'request.comment.create', 'request.attachment.upload', 'request.attachment.download']),
            'approver' => $this->syncRole('Request E2E · Người duyệt', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-participant', 'request.task.view', 'request.task.decide', 'request.task.reassign', 'request.comment.create', 'request.attachment.download', 'request.audit.view']),
            'finance' => $this->syncRole('Request E2E · Tài chính', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-participant', 'request.task.view', 'request.task.decide', 'request.comment.create', 'request.attachment.download', 'request.report.view', 'request.export']),
            'auditor' => $this->syncRole('Request E2E · Kiểm toán', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-all', 'request.audit.view', 'request.report.view', 'request.export', 'request.operation.view']),
        ];

        $users['employee']->syncRoles([$roles['requester']]);
        $users['approver']->syncRoles([$roles['approver']]);
        $users['finance']->syncRoles([$roles['finance']]);
        $users['auditor']->syncRoles([$roles['auditor']]);
        if ($legacyApprover = User::query()->where('email', 'demo@website.test')->first()) {
            $legacyApprover->syncRoles([$roles['approver']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $demoType = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first();
        $configureDemoDefinition = $demoType === null || $this->isPristineDemoDefinition($demoType);
        $this->call(RequestDemoSeeder::class);

        $typeId = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->value('id');
        if ($typeId && $configureDemoDefinition) {
            DB::table('request_types')->where('id', $typeId)->update(['available_from' => $now, 'available_until' => $now->copy()->addDays(90), 'updated_at' => $now]);
            $versionIds = DB::table('request_type_versions')->where('request_type_id', $typeId)->pluck('id');
            foreach ($versionIds as $versionId) {
                DB::table('request_type_audiences')->updateOrInsert(['request_type_version_id' => $versionId, 'actor_type' => 'user', 'actor_id' => $users['employee']->id, 'capability' => 'create'], ['created_at' => $now, 'updated_at' => $now]);
                DB::table('request_stage_definitions')->where('request_type_version_id', $versionId)->where('stage_key', 'manager_review')->update([
                    'resolver_key' => 'fixed_users', 'resolver_config_json' => json_encode(['user_ids' => [$users['approver']->id]], JSON_THROW_ON_ERROR),
                    'sla_minutes' => 1440, 'warning_minutes_before' => 240, 'grace_minutes' => 720, 'timeout_action' => 'suspend',
                    'email_on_assignment' => true, 'email_on_decision' => true, 'email_on_sla_warning' => true, 'updated_at' => $now,
                ]);
            }
        }

        $this->assignDemoDraftToEmployee($users['employee'], $now);
        if ($typeId && $configureDemoDefinition) {
            $this->seedLifecycleMatrix(RequestType::query()->findOrFail($typeId), $users, $now);
        }

        config()->set('request.settings.starter_templates_enabled', true);
        config()->set('request.settings.starter_template_actor_id', $superAdmin->id);
        config()->set('request.settings.starter_template_approver_id', $users['approver']->id);
        $this->call(RequestStarterTemplateSeeder::class);

        $this->command?->newLine();
        $this->command?->info('Request E2E local pack đã sẵn sàng.');
        $this->command?->line('Super Admin: tungocvan@gmail.com (giữ nguyên mật khẩu hiện tại)');
        $this->command?->line('Nhân viên: tungocvan1@gmail.com / '.self::PASSWORD);
        $this->command?->line('Người duyệt: vhdtshop@gmail.com / '.self::PASSWORD);
        $this->command?->line('Tài chính: vansala78@gmail.com / '.self::PASSWORD);
        $this->command?->line('Kiểm toán: hamadaqc01@gmail.com / '.self::PASSWORD);
        $this->command?->line('SLA DEMO: 24 giờ; cảnh báo trước 4 giờ; grace 12 giờ; hết grace sẽ tạm dừng.');
        $this->command?->line('Hiệu lực DEMO: 90 ngày kể từ lần seed local gần nhất; timestamp persistence dùng UTC.');
        $this->command?->line('Ma trận UI: draft, pending, warning, overdue, suspended, approved, rejected, returned, cancelled và failed activation.');
        $this->command?->line('Dữ liệu chi tiết/vận hành: 2 bình luận, 1 tệp đính kèm riêng tư, 1 failed outbox và 1 failed export.');
        $this->command?->line('Tổng cộng: '.DB::table('request_instances')->count().' đề nghị; '.DB::table('request_tasks')->count().' task; '.DB::table('request_types')->count().' loại đề nghị.');
    }

    private function assignDemoDraftToEmployee(User $employee, mixed $now): void
    {
        DB::table('request_instances')->where('request_number', 'DEMO-DRAFT-001')->update([
            'requester_id' => $employee->id,
            'title_snapshot' => 'E2E · Nháp · Đề nghị cấp máy tính xách tay',
            'requester_snapshot_json' => json_encode(['id' => $employee->id, 'display_name' => $employee->name], JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ]);
    }

    /** @param array<string, User> $users */
    private function seedLifecycleMatrix(RequestType $type, array $users, mixed $now): void
    {
        if (InternalRequest::query()->where('title_snapshot', 'E2E · Chờ duyệt bình thường')->exists()) {
            return;
        }

        $employeeId = (int) $users['employee']->id;
        $approverId = (int) $users['approver']->id;

        $pending = $this->submittedScenario($type, $employeeId, 'E2E · Chờ duyệt bình thường');
        $approved = $this->submittedScenario($type, $employeeId, 'E2E · Đã phê duyệt');
        $this->decide($approved, $approverId, DecisionType::Approve);

        $rejected = $this->submittedScenario($type, $employeeId, 'E2E · Đã từ chối');
        $this->decide($rejected, $approverId, DecisionType::Reject, 'Không phù hợp ngân sách DEMO.');

        $returned = $this->submittedScenario($type, $employeeId, 'E2E · Đã trả lại');
        $this->decide($returned, $approverId, DecisionType::Return, 'Vui lòng bổ sung thông tin DEMO.');

        $cancelled = $this->savedScenario($type, $employeeId, 'E2E · Đã hủy');
        app(CancelInternalRequest::class)->handle($cancelled, $employeeId, $cancelled->lock_version, (string) Str::uuid(), 'Người tạo hủy tình huống DEMO.');

        $warning = $this->submittedScenario($type, $employeeId, 'E2E · Sắp đến hạn SLA');
        $overdue = $this->submittedScenario($type, $employeeId, 'E2E · Đã quá hạn SLA');
        $suspended = $this->submittedScenario($type, $employeeId, 'E2E · Tạm dừng do quá hạn');
        $failedActivation = $this->submittedScenario($type, $employeeId, 'E2E · Lỗi kích hoạt cần thử lại');

        $this->setSlaClock($warning, $now->copy()->subHour(), $now->copy()->addHour(), $now->copy()->addHours(13));
        $this->setSlaClock($overdue, $now->copy()->subHours(5), $now->copy()->subHour(), $now->copy()->addHours(11));
        $this->setSlaClock($suspended, $now->copy()->subHours(17), $now->copy()->subHours(13), $now->copy()->subHour());
        app(EnforceRequestTaskSla::class)->handle();

        $failedTask = $failedActivation->refresh()->currentRun->tasks()->firstOrFail();
        $failedTask->candidates()->delete();
        $failedTask->delete();
        $failedActivation->currentRun->update([
            'status' => 'failed_activation',
            'activation_error_code' => 'e2e_actor_resolution_failed',
            'activation_failed_at' => $now,
            'activation_retry_count' => 1,
        ]);

        $this->seedCollaborationFixtures($pending, $employeeId, $approverId, $now);

        $failedOutboxId = DB::table('request_outbox_messages')
            ->where('aggregate_public_id', $pending->public_id)
            ->latest('id')
            ->value('id');
        if ($failedOutboxId) {
            DB::table('request_outbox_messages')->where('id', $failedOutboxId)->update([
                'attempt_count' => 3,
                'last_error_code' => 'e2e_delivery_failed',
                'last_error_at' => $now,
                'failed_at' => $now,
                'updated_at' => $now,
            ]);
        }

        RequestExportJob::query()->create([
            'requested_by' => $users['auditor']->id,
            'filter_snapshot_json' => ['status' => 'pending'],
            'field_snapshot_json' => ['request_number', 'status', 'title'],
            'authorization_scope_json' => ['user_id' => $users['auditor']->id, 'view_all' => true, 'view_own' => false, 'view_participant' => false],
            'format' => 'csv',
            'status' => ExportStatus::Failed,
            'attempt_count' => 2,
            'last_error_code' => 'e2e_export_failed',
            'idempotency_key_hash' => hash('sha256', 'request-e2e-failed-export'),
        ]);
    }

    private function seedCollaborationFixtures(InternalRequest $request, int $employeeId, int $approverId, mixed $now): void
    {
        app(AddRequestComment::class)->handle(
            $request->refresh(),
            'Nhân viên: Đã bổ sung thông tin cấu hình thiết bị để kiểm tra trao đổi trên UI.',
            $employeeId,
            $request->lock_version,
            (string) Str::uuid(),
        );
        app(AddRequestComment::class)->handle(
            $request->refresh(),
            'Người duyệt: Đã nhận đề nghị, vui lòng xác nhận thời gian bàn giao dự kiến.',
            $approverId,
            $request->lock_version,
            (string) Str::uuid(),
        );

        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl9sAAAAASUVORK5CYII=', true);
        if (! is_string($contents)) {
            throw new \RuntimeException('Không thể tạo tệp đính kèm E2E.');
        }

        $disk = (string) config('request.files.disk', 'local');
        $disk = $disk === 'public' ? 'local' : $disk;
        $generated = 'e2e-ui-reference.png';
        $path = 'request/attachments/'.$request->public_id.'/'.$generated;
        if (! Storage::disk($disk)->put($path, $contents)) {
            throw new \RuntimeException('Không thể lưu tệp đính kèm E2E.');
        }

        RequestAttachment::query()->create([
            'request_instance_id' => $request->id,
            'uploaded_by' => $employeeId,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'original_filename' => 'anh-minh-hoa-e2e.png',
            'generated_filename' => $generated,
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'classification' => AttachmentClassification::Internal,
            'scan_status' => AttachmentScanStatus::Clean,
            'scan_metadata_json' => ['driver' => 'e2e_fixture', 'validation' => 'known_safe_fixture'],
            'created_at' => $now,
        ]);
    }

    private function savedScenario(RequestType $type, int $employeeId, string $title): InternalRequest
    {
        $request = app(CreateInternalRequest::class)->handle($type->refresh(), $employeeId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, [
            'item_name' => $title,
            'quantity' => 1,
            'business_reason' => 'Dữ liệu E2E phục vụ kiểm thử nhanh giao diện Request.',
            'confidential_note' => 'Ghi chú nhạy cảm DEMO không được lưu ngoại tuyến.',
        ], $employeeId, 1, (string) Str::uuid());
        $request->update(['title_snapshot' => $title]);

        return $request->refresh();
    }

    private function submittedScenario(RequestType $type, int $employeeId, string $title): InternalRequest
    {
        $request = $this->savedScenario($type, $employeeId, $title);

        return app(SubmitInternalRequest::class)->handle($request, $employeeId, $request->lock_version, (string) Str::uuid())->refresh();
    }

    private function decide(InternalRequest $request, int $approverId, DecisionType $decision, ?string $reason = null): void
    {
        $task = $request->refresh()->currentRun->tasks()->where('status', 'active')->firstOrFail();
        app(DecideRequestTask::class)->handle(
            $task,
            $decision,
            $reason,
            $approverId,
            $request->lock_version,
            $task->lock_version,
            (string) Str::uuid(),
        );
    }

    private function setSlaClock(InternalRequest $request, mixed $warningAt, mixed $dueAt, mixed $graceExpiresAt): void
    {
        $request->refresh()->currentRun->tasks()->where('status', 'active')->update([
            'warning_at' => $warningAt,
            'due_at' => $dueAt,
            'grace_expires_at' => $graceExpiresAt,
        ]);
    }

    private function isPristineDemoDefinition(object $type): bool
    {
        if ((int) $type->lock_version !== 1) {
            return false;
        }

        $versions = DB::table('request_type_versions')
            ->where('request_type_id', $type->id)
            ->orderBy('version_number')
            ->get(['id', 'version_number', 'status']);

        return $versions->count() === 2
            && (int) $versions[0]->id === (int) $type->current_published_version_id
            && (int) $versions[0]->version_number === 1
            && $versions[0]->status === 'published'
            && (int) $versions[1]->id === (int) $type->active_draft_version_id
            && (int) $versions[1]->version_number === 2
            && $versions[1]->status === 'draft';
    }

    private function upsertUser(string $email, string $name, mixed $now): User
    {
        return User::query()->updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make(self::PASSWORD), 'is_active' => true, 'email_verified_at' => $now]);
    }

    private function syncRole(string $name, string $guard, array $permissionNames): Role
    {
        $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        $permissions = Permission::query()->where('guard_name', $guard)->whereIn('name', $permissionNames)->get();
        $role->syncPermissions($permissions);

        return $role;
    }
}
