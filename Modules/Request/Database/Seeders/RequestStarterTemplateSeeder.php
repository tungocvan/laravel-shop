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

        $group = RequestGroup::query()->where('code', 'STARTER')->first();
        if ($group === null) {
            $group = app(CreateRequestGroup::class)->handle([
                'code' => 'STARTER',
                'name' => 'Mẫu khởi đầu',
                'description' => 'Các mẫu Đề nghị tùy chọn để quản trị viên xem xét trước khi phát hành.',
            ], $actorId);
        }

        $type = RequestType::query()->where('code', 'GENERAL_APPROVAL')->first();
        if ($type !== null) {
            return;
        }

        $type = app(CreateRequestType::class)->handle([
            'request_group_id' => $group->id,
            'code' => 'GENERAL_APPROVAL',
            'name' => 'Đề nghị phê duyệt chung',
            'summary' => 'Mẫu khởi đầu một cấp duyệt; luôn được tạo ở trạng thái bản nháp.',
        ], $actorId);

        app(SaveTypeDraft::class)->handle($type, [
            'title' => 'Đề nghị phê duyệt chung',
            'description' => 'Mẫu khởi đầu tùy chọn. Hãy chỉnh sửa đối tượng và người phê duyệt trước khi phát hành.',
            'requester_guidance' => 'Điền nội dung cần phê duyệt và lý do.',
            'form_schema_json' => [
                'schema_version' => 1,
                'sections' => [[
                    'key' => 'details',
                    'label' => 'Thông tin đề nghị',
                    'fields' => [
                        ['key' => 'subject', 'type' => 'text', 'label' => 'Nội dung', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'reason', 'type' => 'textarea', 'label' => 'Lý do', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                    ],
                ]],
            ],
            'policy_json' => [],
            'presentation_json' => [],
            'audiences' => [['actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create']],
            'stages' => [[
                'stage_key' => 'approval',
                'name' => 'Phê duyệt',
                'position' => 1,
                'mode' => 'single',
                'resolver_key' => 'fixed_users',
                'resolver_config_json' => ['user_ids' => [$actorId]],
                'allow_reassignment' => false,
            ]],
        ], $actorId, 1);
    }
}
