<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);
        $now = now();

        DB::transaction(function () use ($actorId, $now): void {
            $groupId = DB::table('request_groups')->where('code', 'REQUEST_UI_DEMO')->value('id');
            if (! $groupId) {
                $groupId = DB::table('request_groups')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'code' => 'REQUEST_UI_DEMO',
                    'name' => 'DEMO · Kiểm thử giao diện',
                    'description' => 'Dữ liệu mẫu phục vụ kiểm thử UI-01 đến UI-07 của phân hệ Đề nghị.',
                    'icon_key' => 'clipboard-check',
                    'color_key' => 'blue',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('request_groups')->where('id', $groupId)->update([
                    'name' => 'DEMO · Kiểm thử giao diện',
                    'description' => 'Dữ liệu mẫu phục vụ kiểm thử UI-01 đến UI-07 của phân hệ Đề nghị.',
                    'is_active' => true,
                    'archived_at' => null,
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
            }

            $type = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first();
            if (! $type) {
                $typeId = DB::table('request_types')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_group_id' => $groupId,
                    'code' => 'REQUEST_UI_DEMO',
                    'name' => 'DEMO · Đề nghị cấp thiết bị',
                    'summary' => 'Biểu mẫu mẫu để kiểm thử giao diện thích ứng, chế độ ngoại tuyến và dữ liệu nhạy cảm.',
                    'status' => 'published',
                    'sort_order' => 1,
                    'lock_version' => 1,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $type = DB::table('request_types')->where('id', $typeId)->first();
            } else {
                DB::table('request_types')->where('id', $type->id)->update([
                    'request_group_id' => $groupId,
                    'name' => 'DEMO · Đề nghị cấp thiết bị',
                    'summary' => 'Biểu mẫu mẫu để kiểm thử giao diện thích ứng, chế độ ngoại tuyến và dữ liệu nhạy cảm.',
                    'status' => 'published',
                    'available_from' => null,
                    'available_until' => null,
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
                $type = DB::table('request_types')->where('id', $type->id)->first();
            }

            $schema = [
                'schema_version' => 1,
                'sections' => [[
                    'key' => 'request_details',
                    'label' => 'Thông tin đề nghị',
                    'fields' => [
                        ['key' => 'item_name', 'type' => 'text', 'label' => 'Tên thiết bị', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'quantity', 'type' => 'integer', 'label' => 'Số lượng', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'business_reason', 'type' => 'textarea', 'label' => 'Lý do sử dụng', 'required' => false, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'confidential_note', 'type' => 'textarea', 'label' => 'Ghi chú bảo mật', 'required' => false, 'classification' => 'confidential', 'offline_draft' => false],
                    ],
                ]],
            ];

            $publishedId = DB::table('request_type_versions')
                ->where('request_type_id', $type->id)
                ->where('version_number', 1)
                ->value('id');

            $publishedValues = [
                'status' => 'published',
                'title' => 'DEMO · Đề nghị cấp thiết bị',
                'description' => 'Phiên bản đã phát hành để kiểm thử danh mục và luồng tạo đề nghị.',
                'requester_guidance' => 'Chỉ sử dụng loại đề nghị này để kiểm thử giao diện.',
                'form_schema_json' => json_encode($schema, JSON_THROW_ON_ERROR),
                'policy_json' => json_encode([], JSON_THROW_ON_ERROR),
                'presentation_json' => json_encode([], JSON_THROW_ON_ERROR),
                'schema_version' => 1,
                'published_by' => $actorId,
                'published_at' => $now,
                'updated_by' => $actorId,
                'updated_at' => $now,
            ];

            if (! $publishedId) {
                $publishedId = DB::table('request_type_versions')->insertGetId($publishedValues + [
                    'public_id' => (string) Str::ulid(),
                    'request_type_id' => $type->id,
                    'version_number' => 1,
                    'created_by' => $actorId,
                    'created_at' => $now,
                ]);
            } else {
                DB::table('request_type_versions')->where('id', $publishedId)->update($publishedValues);
            }

            $draftId = DB::table('request_type_versions')
                ->where('request_type_id', $type->id)
                ->where('status', 'draft')
                ->value('id');

            if (! $draftId) {
                $draftId = DB::table('request_type_versions')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_type_id' => $type->id,
                    'version_number' => 2,
                    'status' => 'draft',
                    'title' => 'DEMO · Đề nghị cấp thiết bị v2',
                    'description' => 'Bản nháp dùng để kiểm thử trình thiết kế trên máy tính bảng.',
                    'requester_guidance' => 'Thử thêm, xóa, di chuyển trường hoặc cấp duyệt rồi lưu lại.',
                    'form_schema_json' => json_encode($schema, JSON_THROW_ON_ERROR),
                    'policy_json' => json_encode([], JSON_THROW_ON_ERROR),
                    'presentation_json' => json_encode([], JSON_THROW_ON_ERROR),
                    'schema_version' => 1,
                    'created_from_version_id' => $publishedId,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('request_type_versions')->where('id', $draftId)->update([
                    'title' => 'DEMO · Đề nghị cấp thiết bị v2',
                    'description' => 'Bản nháp dùng để kiểm thử trình thiết kế trên máy tính bảng.',
                    'requester_guidance' => 'Thử thêm, xóa, di chuyển trường hoặc cấp duyệt rồi lưu lại.',
                    'form_schema_json' => json_encode($schema, JSON_THROW_ON_ERROR),
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
            }

            DB::table('request_types')->where('id', $type->id)->update([
                'status' => 'published',
                'current_published_version_id' => $publishedId,
                'active_draft_version_id' => $draftId,
                'updated_by' => $actorId,
                'updated_at' => $now,
            ]);

            foreach ([$publishedId, $draftId] as $versionId) {
                DB::table('request_type_audiences')->updateOrInsert(
                    ['request_type_version_id' => $versionId, 'actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create'],
                    ['created_at' => $now, 'updated_at' => $now]
                );

                $stage = DB::table('request_stage_definitions')
                    ->where('request_type_version_id', $versionId)
                    ->where('stage_key', 'manager_review')
                    ->first();

                $stageValues = [
                    'name' => 'Quản lý phê duyệt',
                    'position' => 1,
                    'mode' => 'single',
                    'resolver_key' => 'fixed_user',
                    'resolver_config_json' => json_encode(['user_id' => $actorId], JSON_THROW_ON_ERROR),
                    'instructions' => 'Cấp duyệt DEMO để kiểm thử bàn phím và thao tác quyết định.',
                    'allow_reassignment' => true,
                    'updated_at' => $now,
                ];

                if ($stage) {
                    DB::table('request_stage_definitions')->where('id', $stage->id)->update($stageValues);
                } else {
                    DB::table('request_stage_definitions')->insert($stageValues + [
                        'public_id' => (string) Str::ulid(),
                        'request_type_version_id' => $versionId,
                        'stage_key' => 'manager_review',
                        'created_at' => $now,
                    ]);
                }
            }

            $draftRequest = DB::table('request_instances')->where('request_number', 'DEMO-DRAFT-001')->first();
            if (! $draftRequest) {
                $requestId = DB::table('request_instances')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_number' => 'DEMO-DRAFT-001',
                    'request_type_id' => $type->id,
                    'request_type_version_id' => $publishedId,
                    'requester_id' => $actorId,
                    'status' => 'draft',
                    'title_snapshot' => 'DEMO · Đề nghị thay máy tính xách tay',
                    'requester_snapshot_json' => json_encode(['user_id' => $actorId, 'display_name' => 'Người dùng Demo'], JSON_THROW_ON_ERROR),
                    'lock_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $payloadId = DB::table('request_payload_revisions')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_instance_id' => $requestId,
                    'revision_number' => 1,
                    'request_type_version_id' => $publishedId,
                    'payload_json' => json_encode(['item_name' => 'Máy tính xách tay', 'quantity' => 1, 'business_reason' => 'Tình huống DEMO cho bản nháp ngoại tuyến', 'confidential_note' => 'Giá trị này không được phép lưu ngoại tuyến'], JSON_THROW_ON_ERROR),
                    'display_snapshot_json' => json_encode(['item_name' => 'Máy tính xách tay', 'quantity' => 1, 'business_reason' => 'Tình huống DEMO cho bản nháp ngoại tuyến'], JSON_THROW_ON_ERROR),
                    'payload_checksum' => hash('sha256', 'REQUEST_UI_DEMO:DEMO-DRAFT-001:1'),
                    'schema_version' => 1,
                    'source' => 'server_draft',
                    'created_by' => $actorId,
                    'created_at' => $now,
                ]);

                DB::table('request_instances')->where('id', $requestId)->update(['current_payload_revision_id' => $payloadId]);
            } else {
                DB::table('request_instances')->where('id', $draftRequest->id)->update([
                    'requester_id' => $actorId,
                    'title_snapshot' => 'DEMO · Đề nghị thay máy tính xách tay',
                    'updated_at' => $now,
                ]);
            }
        });

        $this->command?->info('Dữ liệu DEMO Request đã sẵn sàng. Mở /admin/requests');
    }
}
