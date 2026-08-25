<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

class RequestStarterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.starter_templates_enabled', false) !== true) {
            return;
        }

        $actorId = (int) config('request.settings.starter_template_actor_id', 0);
        $activeActor = $actorId > 0 && DB::table('users')->where('id', $actorId)->where('is_active', true)->exists();
        if ($activeActor === false) {
            return;
        }

        $approverId = (int) config('request.settings.starter_template_approver_id', 0);
        if ($approverId <= 0 || ! DB::table('users')->where('id', $approverId)->where('is_active', true)->exists() || $approverId === $actorId) {
            $approverId = (int) (DB::table('users')
                ->where('id', '!=', $actorId)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id') ?? 0);
        }

        if ($approverId <= 0) {
            return;
        }

        $group = RequestGroup::query()->where('code', 'STARTER')->first();
        if ($group === null) {
            $group = app(CreateRequestGroup::class)->handle([
                'code' => 'STARTER',
                'name' => 'Mẫu khởi đầu',
                'description' => 'Các mẫu Đề nghị dựng sẵn để quản trị viên chỉnh sửa rồi phát hành, hạn chế phải tạo biểu mẫu từ đầu.',
            ], $actorId);
        }

        foreach ($this->templates($actorId, $approverId) as $template) {
            $type = RequestType::query()->where('code', $template['code'])->first();
            if ($type !== null) {
                continue;
            }

            $type = app(CreateRequestType::class)->handle([
                'request_group_id' => $group->id,
                'code' => $template['code'],
                'name' => $template['name'],
                'summary' => $template['summary'],
            ], $actorId);

            app(SaveTypeDraft::class)->handle($type, [
                'title' => $template['name'],
                'description' => $template['description'],
                'requester_guidance' => $template['guidance'],
                'form_schema_json' => $template['schema'],
                'policy_json' => [],
                'presentation_json' => [],
                'audiences' => [['actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create']],
                'stages' => [[
                    'stage_key' => 'approval',
                    'name' => 'Phê duyệt',
                    'position' => 1,
                    'mode' => 'single',
                    'resolver_key' => 'fixed_users',
                    'resolver_config_json' => ['user_ids' => [$approverId]],
                    'allow_reassignment' => true,
                ]],
            ], $actorId, 1);
        }
    }

    private function templates(int $actorId, int $approverId): array
    {
        return [
            [
                'code' => 'GENERAL_APPROVAL',
                'name' => 'Đề nghị phê duyệt chung',
                'summary' => 'Mẫu một cấp duyệt cho các nhu cầu nội bộ thông thường.',
                'description' => 'Mẫu khởi đầu tổng quát. Chỉnh sửa trường dữ liệu và cấp duyệt trước khi phát hành.',
                'guidance' => 'Điền nội dung cần phê duyệt, lý do và thời hạn mong muốn.',
                'schema' => $this->schema([
                    ['key' => 'subject', 'type' => 'text', 'label' => 'Nội dung đề nghị', 'required' => true],
                    ['key' => 'reason', 'type' => 'textarea', 'label' => 'Lý do', 'required' => true],
                    ['key' => 'needed_by', 'type' => 'date', 'label' => 'Ngày cần hoàn tất', 'required' => false],
                ]),
            ],
            [
                'code' => 'EQUIPMENT_PURCHASE',
                'name' => 'Đề nghị mua / cấp thiết bị',
                'summary' => 'Mẫu cho laptop, máy tính, điện thoại, thiết bị văn phòng hoặc công cụ làm việc.',
                'description' => 'Mẫu thiết bị dựng sẵn với số lượng, lý do sử dụng và mức ưu tiên.',
                'guidance' => 'Mô tả thiết bị cần cấp, số lượng và lý do sử dụng.',
                'schema' => $this->schema([
                    ['key' => 'item_name', 'type' => 'text', 'label' => 'Tên thiết bị', 'required' => true],
                    ['key' => 'quantity', 'type' => 'integer', 'label' => 'Số lượng', 'required' => true],
                    ['key' => 'priority', 'type' => 'select', 'label' => 'Mức ưu tiên', 'required' => true, 'options' => [['key' => 'normal', 'label' => 'Bình thường'], ['key' => 'urgent', 'label' => 'Gấp']]],
                    ['key' => 'business_reason', 'type' => 'textarea', 'label' => 'Lý do sử dụng', 'required' => true],
                ]),
            ],
            [
                'code' => 'LEAVE_REQUEST',
                'name' => 'Đề nghị nghỉ phép',
                'summary' => 'Mẫu nghỉ phép có ngày bắt đầu, ngày kết thúc và thông tin bàn giao.',
                'description' => 'Mẫu nghỉ phép dựng sẵn để doanh nghiệp điều chỉnh chính sách và cấp duyệt.',
                'guidance' => 'Chọn thời gian nghỉ và ghi rõ phương án bàn giao công việc.',
                'schema' => $this->schema([
                    ['key' => 'leave_type', 'type' => 'select', 'label' => 'Loại nghỉ', 'required' => true, 'options' => [['key' => 'annual', 'label' => 'Nghỉ phép năm'], ['key' => 'personal', 'label' => 'Nghỉ việc riêng'], ['key' => 'other', 'label' => 'Khác']]],
                    ['key' => 'from_date', 'type' => 'date', 'label' => 'Từ ngày', 'required' => true],
                    ['key' => 'to_date', 'type' => 'date', 'label' => 'Đến ngày', 'required' => true],
                    ['key' => 'handover', 'type' => 'textarea', 'label' => 'Kế hoạch bàn giao', 'required' => false],
                ]),
            ],
            [
                'code' => 'EXPENSE_REIMBURSEMENT',
                'name' => 'Đề nghị thanh toán / hoàn ứng',
                'summary' => 'Mẫu thanh toán chi phí với số tiền, nội dung và thông tin chứng từ.',
                'description' => 'Mẫu tài chính dựng sẵn; có thể bổ sung attachment hoặc thêm cấp duyệt tài chính trước khi phát hành.',
                'guidance' => 'Nhập số tiền, nội dung chi và thông tin chứng từ liên quan.',
                'schema' => $this->schema([
                    ['key' => 'expense_subject', 'type' => 'text', 'label' => 'Nội dung chi', 'required' => true],
                    ['key' => 'amount', 'type' => 'currency', 'label' => 'Số tiền', 'required' => true],
                    ['key' => 'expense_date', 'type' => 'date', 'label' => 'Ngày phát sinh', 'required' => true],
                    ['key' => 'invoice_note', 'type' => 'textarea', 'label' => 'Thông tin chứng từ', 'required' => false],
                ]),
            ],
        ];
    }

    private function schema(array $fields): array
    {
        return [
            'schema_version' => 1,
            'sections' => [[
                'key' => 'details',
                'label' => 'Thông tin đề nghị',
                'fields' => array_map(fn (array $field): array => $field + [
                    'classification' => 'internal',
                    'offline_draft' => true,
                ], $fields),
            ]],
        ];
    }
}
