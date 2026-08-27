<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\User\Contracts\UserDirectory;

class RequestOffboardingHandoverSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.starter_templates_enabled', false) !== true) {
            return;
        }

        $actorId = (int) config('request.settings.starter_template_actor_id', 0);
        $configuredApproverId = (int) config('request.settings.starter_template_approver_id', 0);
        $directory = app(UserDirectory::class);

        if ($actorId <= 0 || $directory->findActive($actorId) === null) {
            return;
        }

        $activeUsers = collect($directory->searchActive('@', 100))
            ->filter(fn ($identity): bool => (int) $identity->id !== $actorId)
            ->values();

        $firstApproverId = $configuredApproverId > 0 && $directory->findActive($configuredApproverId) !== null
            ? $configuredApproverId
            : (int) ($activeUsers->first()?->id ?? 0);

        if ($firstApproverId <= 0) {
            return;
        }

        $secondApproverId = (int) ($activeUsers
            ->first(fn ($identity): bool => (int) $identity->id !== $firstApproverId)?->id ?? $firstApproverId);

        $group = RequestGroup::query()->where('code', 'HR_ADMIN')->first();
        if ($group === null) {
            $group = app(CreateRequestGroup::class)->handle([
                'code' => 'HR_ADMIN',
                'name' => 'Nhân sự & Hành chính',
                'description' => 'Các đề nghị liên quan nhân sự, nghỉ việc, bàn giao công việc, tài sản và hoàn tất thủ tục nội bộ.',
            ], $actorId);
        }

        if (RequestType::query()->where('code', 'OFFBOARDING_HANDOVER')->exists()) {
            return;
        }

        $type = app(CreateRequestType::class)->handle([
            'request_group_id' => $group->id,
            'code' => 'OFFBOARDING_HANDOVER',
            'name' => 'Bàn giao công việc & tài sản khi nghỉ việc',
            'summary' => 'Mẫu bàn giao chuẩn khi nhân sự nghỉ việc, gồm công việc, hồ sơ, quyền truy cập, tài sản và xác nhận hoàn tất.',
        ], $actorId);

        app(SaveTypeDraft::class)->handle($type, [
            'title' => 'Bàn giao công việc & tài sản khi nghỉ việc',
            'description' => 'Biểu mẫu dùng để lập kế hoạch bàn giao công việc, hồ sơ, tài sản và các nghĩa vụ còn tồn trước ngày nghỉ việc.',
            'requester_guidance' => 'Điền đầy đủ người nghỉ việc, người nhận bàn giao, ngày làm việc cuối cùng, công việc còn tồn, tài sản phải hoàn trả và đính kèm biên bản nếu có.',
            'form_schema_json' => [
                'schema_version' => 1,
                'sections' => [
                    [
                        'key' => 'employee_and_schedule',
                        'label' => 'Thông tin nhân sự & thời gian nghỉ việc',
                        'fields' => [
                            ['key' => 'departing_employee', 'type' => 'user', 'label' => 'Nhân sự nghỉ việc', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half'],
                            ['key' => 'direct_manager', 'type' => 'user', 'label' => 'Quản lý trực tiếp', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half'],
                            ['key' => 'department', 'type' => 'text', 'label' => 'Phòng / bộ phận', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half'],
                            ['key' => 'last_working_date', 'type' => 'date', 'label' => 'Ngày làm việc cuối cùng', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half'],
                            ['key' => 'handover_recipient', 'type' => 'user', 'label' => 'Người nhận bàn giao chính', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                        ],
                    ],
                    [
                        'key' => 'work_handover',
                        'label' => 'Bàn giao công việc & hồ sơ',
                        'fields' => [
                            ['key' => 'ongoing_work', 'type' => 'textarea', 'label' => 'Công việc / dự án đang thực hiện', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'pending_tasks', 'type' => 'textarea', 'label' => 'Việc còn tồn & thời hạn cần theo dõi', 'required' => false, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'documents_and_locations', 'type' => 'textarea', 'label' => 'Hồ sơ, tài liệu & nơi lưu trữ', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'accounts_and_access', 'type' => 'textarea', 'label' => 'Tài khoản / quyền truy cập cần chuyển giao hoặc thu hồi', 'required' => false, 'classification' => 'confidential', 'offline_draft' => false, 'width' => 'full'],
                            ['key' => 'work_handover_attachment', 'type' => 'attachment', 'label' => 'Biên bản / tài liệu bàn giao công việc', 'required' => false, 'classification' => 'internal', 'offline_draft' => false, 'width' => 'full'],
                        ],
                    ],
                    [
                        'key' => 'asset_handover',
                        'label' => 'Bàn giao tài sản & nghĩa vụ còn tồn',
                        'fields' => [
                            ['key' => 'company_assets', 'type' => 'textarea', 'label' => 'Danh sách tài sản công ty đang giữ', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'asset_recipient', 'type' => 'user', 'label' => 'Người / bộ phận nhận tài sản', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half'],
                            ['key' => 'asset_status', 'type' => 'select', 'label' => 'Tình trạng bàn giao tài sản', 'required' => true, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'half', 'options' => [
                                ['key' => 'returned', 'label' => 'Đã bàn giao đầy đủ'],
                                ['key' => 'partial', 'label' => 'Đã bàn giao một phần'],
                                ['key' => 'pending', 'label' => 'Chưa bàn giao'],
                                ['key' => 'damaged_or_lost', 'label' => 'Hư hỏng / thất lạc cần xử lý'],
                            ]],
                            ['key' => 'financial_or_admin_outstanding', 'type' => 'textarea', 'label' => 'Tạm ứng, công nợ, hồ sơ hoặc nghĩa vụ còn tồn', 'required' => false, 'classification' => 'confidential', 'offline_draft' => false, 'width' => 'full'],
                            ['key' => 'asset_handover_attachment', 'type' => 'attachment', 'label' => 'Biên bản / hình ảnh bàn giao tài sản', 'required' => false, 'classification' => 'internal', 'offline_draft' => false, 'width' => 'full'],
                        ],
                    ],
                    [
                        'key' => 'final_confirmation',
                        'label' => 'Xác nhận hoàn tất',
                        'fields' => [
                            ['key' => 'employee_note', 'type' => 'textarea', 'label' => 'Ghi chú của nhân sự nghỉ việc', 'required' => false, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'manager_note', 'type' => 'textarea', 'label' => 'Ghi chú của quản lý / người nhận bàn giao', 'required' => false, 'classification' => 'internal', 'offline_draft' => true, 'width' => 'full'],
                            ['key' => 'hr_admin_note', 'type' => 'textarea', 'label' => 'Ghi chú Nhân sự / Hành chính', 'required' => false, 'classification' => 'confidential', 'offline_draft' => false, 'width' => 'full'],
                        ],
                    ],
                ],
            ],
            'policy_json' => [],
            'presentation_json' => [],
            'audiences' => [['actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create']],
            'stages' => [
                [
                    'stage_key' => 'manager_handover_confirmation',
                    'name' => 'Xác nhận bàn giao công việc',
                    'position' => 1,
                    'mode' => 'single',
                    'resolver_key' => 'fixed_users',
                    'resolver_config_json' => ['user_ids' => [$firstApproverId]],
                    'instructions' => 'Kiểm tra người nhận bàn giao, công việc còn tồn, hồ sơ và quyền truy cập trước khi xác nhận.',
                    'allow_reassignment' => true,
                    'sla_minutes' => 1440,
                    'warning_minutes_before' => 240,
                    'grace_minutes' => 0,
                    'timeout_action' => 'notify_only',
                    'email_on_assignment' => false,
                    'email_on_decision' => false,
                    'email_on_sla_warning' => false,
                ],
                [
                    'stage_key' => 'asset_and_hr_clearance',
                    'name' => 'Xác nhận tài sản & hoàn tất nghỉ việc',
                    'position' => 2,
                    'mode' => 'single',
                    'resolver_key' => 'fixed_users',
                    'resolver_config_json' => ['user_ids' => [$secondApproverId]],
                    'instructions' => 'Kiểm tra tài sản, tạm ứng / công nợ, hồ sơ và các nghĩa vụ còn tồn trước khi hoàn tất thủ tục nghỉ việc.',
                    'allow_reassignment' => true,
                    'sla_minutes' => 1440,
                    'warning_minutes_before' => 240,
                    'grace_minutes' => 0,
                    'timeout_action' => 'notify_only',
                    'email_on_assignment' => false,
                    'email_on_decision' => false,
                    'email_on_sla_warning' => false,
                ],
            ],
        ], $actorId, 1);
    }
}
