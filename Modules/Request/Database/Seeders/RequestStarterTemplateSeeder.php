<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\User\Contracts\UserDirectory;

class RequestStarterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.starter_templates_enabled', false) !== true) {
            return;
        }

        $actorId = (int) config('request.settings.starter_template_actor_id', 0);
        $approverId = (int) config('request.settings.starter_template_approver_id', 0);
        $directory = app(UserDirectory::class);

        if ($actorId <= 0 || $directory->findActive($actorId) === null) {
            return;
        }

        if ($approverId <= 0 || $approverId === $actorId || $directory->findActive($approverId) === null) {
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

        foreach ($this->templates() as $template) {
            $type = RequestType::query()->where('code', $template['code'])->first();
            if ($type !== null) {
                continue;
            }

            $typeGroup = $group;
            if (is_array($template['group'] ?? null)) {
                $typeGroup = RequestGroup::query()->where('code', $template['group']['code'])->first();
                if ($typeGroup === null) {
                    $typeGroup = app(CreateRequestGroup::class)->handle($template['group'], $actorId);
                }
            }

            $type = app(CreateRequestType::class)->handle([
                'request_group_id' => $typeGroup->id,
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
                    'name' => $template['approval_stage_name'] ?? 'Phê duyệt',
                    'position' => 1,
                    'mode' => 'single',
                    'resolver_key' => 'fixed_users',
                    'resolver_config_json' => ['user_ids' => [$approverId]],
                    'allow_reassignment' => true,
                    'sla_minutes' => 1440,
                    'warning_minutes_before' => 240,
                    'grace_minutes' => 0,
                    'timeout_action' => 'notify_only',
                    'email_on_assignment' => true,
                    'email_on_decision' => true,
                    'email_on_sla_warning' => true,
                ]],
            ], $actorId, 1);
        }
    }

    private function templates(): array
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
                'name' => 'Đề xuất tạm ứng chi phí',
                'summary' => 'Một biểu mẫu dùng chung cho tiếp khách, công tác, bán hàng, marketing, sự kiện và các chi phí kinh doanh khác.',
                'description' => 'Đề xuất xin phê duyệt và tạm ứng trước khi phát sinh chi phí của Phòng Kinh doanh.',
                'guidance' => 'Chọn nhóm chi phí, trình bày mục đích, thời gian, dự toán và kế hoạch hoàn ứng. Đính kèm báo giá hoặc kế hoạch nếu có.',
                'approval_stage_name' => 'Phê duyệt đề xuất tạm ứng',
                'group' => [
                    'code' => 'SALES',
                    'name' => 'Phòng Kinh doanh',
                    'description' => 'Các đề xuất phục vụ hoạt động bán hàng, chăm sóc khách hàng và phát triển thị trường.',
                ],
                'schema' => $this->sectionedSchema([
                    [
                        'key' => 'proposal_overview',
                        'label' => 'Thông tin đề xuất',
                        'fields' => [
                            ['key' => 'proposal_title', 'type' => 'text', 'label' => 'Tiêu đề đề xuất', 'required' => true, 'width' => 'half', 'validation' => ['max_length' => 200], 'help' => 'Ví dụ: Tạm ứng chi phí tiếp khách Công ty ABC tháng 9/2026.'],
                            ['key' => 'expense_category', 'type' => 'select', 'label' => 'Nhóm chi phí', 'required' => true, 'width' => 'half', 'options' => [
                                ['key' => 'customer_entertainment', 'label' => 'Chi phí tiếp khách'],
                                ['key' => 'business_trip', 'label' => 'Chi phí đi công tác'],
                                ['key' => 'sales', 'label' => 'Chi phí bán hàng'],
                                ['key' => 'marketing', 'label' => 'Marketing / quảng cáo'],
                                ['key' => 'event', 'label' => 'Hội nghị / sự kiện'],
                                ['key' => 'sample_or_gift', 'label' => 'Hàng mẫu / quà tặng'],
                                ['key' => 'transport', 'label' => 'Giao nhận / vận chuyển'],
                                ['key' => 'other', 'label' => 'Chi phí khác'],
                            ]],
                            ['key' => 'other_expense_category', 'type' => 'text', 'label' => 'Tên nhóm chi phí khác', 'required' => true, 'width' => 'full', 'validation' => ['max_length' => 200], 'visible_when' => ['field' => 'expense_category', 'operator' => 'equals', 'value' => 'other']],
                            ['key' => 'purpose', 'type' => 'textarea', 'label' => 'Mục đích và lý do đề xuất', 'required' => true, 'width' => 'full', 'validation' => ['max_length' => 2000], 'help' => 'Nêu bối cảnh, đối tượng phục vụ và lý do cần tạm ứng.'],
                            ['key' => 'expected_result', 'type' => 'textarea', 'label' => 'Kết quả / hiệu quả mong đợi', 'required' => false, 'width' => 'full', 'validation' => ['max_length' => 2000]],
                        ],
                    ],
                    [
                        'key' => 'expense_plan',
                        'label' => 'Kế hoạch và dự toán chi phí',
                        'fields' => [
                            ['key' => 'sales_team', 'type' => 'text', 'label' => 'Đơn vị / nhóm thuộc Phòng Kinh doanh', 'required' => false, 'width' => 'full', 'validation' => ['max_length' => 150]],
                            ['key' => 'needed_on', 'type' => 'date', 'label' => 'Ngày cần nhận tiền', 'required' => false, 'width' => 'third', 'default' => 'today'],
                            ['key' => 'expense_from', 'type' => 'date', 'label' => 'Dự kiến chi từ ngày', 'required' => false, 'width' => 'third', 'default' => 'today'],
                            ['key' => 'expense_to', 'type' => 'date', 'label' => 'Dự kiến chi đến ngày', 'required' => false, 'width' => 'third', 'default' => 'today'],
                            ['key' => 'advance_amount_vnd', 'type' => 'integer', 'label' => 'Số tiền đề nghị tạm ứng (VND)', 'required' => false, 'width' => 'half', 'validation' => ['min' => 1, 'max' => 1000000000000]],
                            ['key' => 'budget_status', 'type' => 'select', 'label' => 'Tình trạng ngân sách', 'required' => false, 'width' => 'half', 'options' => [
                                ['key' => 'planned', 'label' => 'Đã có trong ngân sách / kế hoạch'],
                                ['key' => 'unplanned', 'label' => 'Ngoài ngân sách / phát sinh'],
                            ]],
                            ['key' => 'cost_breakdown', 'type' => 'textarea', 'label' => 'Chi tiết các hạng mục và dự toán', 'required' => false, 'width' => 'full', 'validation' => ['max_length' => 4000], 'help' => 'Có thể liệt kê từng hạng mục, số lượng, đơn giá và thành tiền dự kiến.'],
                        ],
                    ],
                    [
                        'key' => 'payment_and_settlement',
                        'label' => 'Nhận tiền và hoàn ứng',
                        'fields' => [
                            ['key' => 'advance_recipient', 'type' => 'text', 'label' => 'Người nhận tạm ứng', 'required' => false, 'width' => 'half', 'validation' => ['max_length' => 200]],
                            ['key' => 'payment_method', 'type' => 'select', 'label' => 'Hình thức nhận tiền', 'required' => false, 'width' => 'half', 'options' => [
                                ['key' => 'bank_transfer', 'label' => 'Chuyển khoản'],
                                ['key' => 'cash', 'label' => 'Tiền mặt'],
                            ]],
                            ['key' => 'bank_information', 'type' => 'textarea', 'label' => 'Thông tin tài khoản nhận tiền', 'required' => false, 'width' => 'full', 'validation' => ['max_length' => 500], 'visible_when' => ['field' => 'payment_method', 'operator' => 'equals', 'value' => 'bank_transfer'], 'help' => 'Nếu cần chuyển khoản, ghi chủ tài khoản, số tài khoản và ngân hàng.'],
                            ['key' => 'previous_advance_status', 'type' => 'select', 'label' => 'Tình trạng khoản tạm ứng trước', 'required' => false, 'width' => 'half', 'options' => [
                                ['key' => 'none', 'label' => 'Không có khoản tạm ứng trước'],
                                ['key' => 'settled', 'label' => 'Đã hoàn ứng đầy đủ'],
                                ['key' => 'outstanding', 'label' => 'Còn khoản chưa hoàn ứng'],
                            ]],
                            ['key' => 'settlement_due_on', 'type' => 'date', 'label' => 'Ngày dự kiến hoàn ứng', 'required' => false, 'width' => 'half', 'default' => 'today'],
                            ['key' => 'supporting_documents', 'type' => 'attachment', 'label' => 'Báo giá, kế hoạch hoặc tài liệu liên quan', 'required' => false, 'width' => 'full', 'classification' => 'confidential', 'offline_draft' => false, 'validation' => ['max_count' => 5], 'help' => 'Có thể chọn cùng lúc tối đa 5 tệp: PDF, PNG, JPG, DOCX hoặc XLSX; mỗi tệp tối đa 10 MB.'],
                        ],
                    ],
                ]),
            ],
        ];
    }

    private function schema(array $fields): array
    {
        return $this->sectionedSchema([[
            'key' => 'details',
            'label' => 'Thông tin đề nghị',
            'fields' => $fields,
        ]]);
    }

    private function sectionedSchema(array $sections): array
    {
        return [
            'schema_version' => 1,
            'sections' => array_map(function (array $section): array {
                $section['fields'] = array_map(fn (array $field): array => $field + [
                    'classification' => 'internal',
                    'offline_draft' => true,
                ], (array) ($section['fields'] ?? []));

                return $section;
            }, $sections),
        ];
    }
}
