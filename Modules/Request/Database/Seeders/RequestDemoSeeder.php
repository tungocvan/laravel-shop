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
                    'name' => 'DEMO · UI Acceptance',
                    'description' => 'Seed data for Request MR-08 UI acceptance testing.',
                    'icon_key' => 'clipboard-check',
                    'color_key' => 'blue',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $type = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first();
            if (! $type) {
                $typeId = DB::table('request_types')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_group_id' => $groupId,
                    'code' => 'REQUEST_UI_DEMO',
                    'name' => 'DEMO · Equipment Request',
                    'summary' => 'Responsive/offline test form with safe and confidential fields.',
                    'status' => 'published',
                    'sort_order' => 1,
                    'lock_version' => 1,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $type = DB::table('request_types')->where('id', $typeId)->first();
            }

            $schema = [
                'schema_version' => 1,
                'sections' => [[
                    'key' => 'request_details',
                    'label' => 'Request details',
                    'fields' => [
                        ['key' => 'item_name', 'type' => 'text', 'label' => 'Item name', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'quantity', 'type' => 'number', 'label' => 'Quantity', 'required' => true, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'business_reason', 'type' => 'textarea', 'label' => 'Business reason', 'required' => false, 'classification' => 'internal', 'offline_draft' => true],
                        ['key' => 'confidential_note', 'type' => 'textarea', 'label' => 'Confidential note', 'required' => false, 'classification' => 'confidential', 'offline_draft' => false],
                    ],
                ]],
            ];

            $publishedId = DB::table('request_type_versions')
                ->where('request_type_id', $type->id)
                ->where('version_number', 1)
                ->value('id');

            if (! $publishedId) {
                $publishedId = DB::table('request_type_versions')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_type_id' => $type->id,
                    'version_number' => 1,
                    'status' => 'published',
                    'title' => 'DEMO · Equipment Request',
                    'description' => 'Published demo version for catalog/create testing.',
                    'requester_guidance' => 'Use this type only for UI acceptance tests.',
                    'form_schema_json' => json_encode($schema, JSON_THROW_ON_ERROR),
                    'policy_json' => json_encode([], JSON_THROW_ON_ERROR),
                    'presentation_json' => json_encode([], JSON_THROW_ON_ERROR),
                    'schema_version' => 1,
                    'published_by' => $actorId,
                    'published_at' => $now,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
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
                    'title' => 'DEMO · Equipment Request v2',
                    'description' => 'Editable draft used for tablet designer testing.',
                    'requester_guidance' => 'Try add/remove/move controls, then save.',
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

                if (! DB::table('request_stage_definitions')->where('request_type_version_id', $versionId)->where('stage_key', 'manager_review')->exists()) {
                    DB::table('request_stage_definitions')->insert([
                        'public_id' => (string) Str::ulid(),
                        'request_type_version_id' => $versionId,
                        'stage_key' => 'manager_review',
                        'name' => 'Manager review',
                        'position' => 1,
                        'mode' => 'sequential',
                        'resolver_key' => 'fixed_user',
                        'resolver_config_json' => json_encode(['user_id' => $actorId], JSON_THROW_ON_ERROR),
                        'instructions' => 'DEMO stage for keyboard and decision UI testing.',
                        'allow_reassignment' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            if (! DB::table('request_instances')->where('request_number', 'DEMO-DRAFT-001')->exists()) {
                $requestId = DB::table('request_instances')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_number' => 'DEMO-DRAFT-001',
                    'request_type_id' => $type->id,
                    'request_type_version_id' => $publishedId,
                    'requester_id' => $actorId,
                    'status' => 'draft',
                    'title_snapshot' => 'DEMO · Laptop replacement request',
                    'requester_snapshot_json' => json_encode(['user_id' => $actorId, 'display_name' => 'Demo User'], JSON_THROW_ON_ERROR),
                    'lock_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $payloadId = DB::table('request_payload_revisions')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'request_instance_id' => $requestId,
                    'revision_number' => 1,
                    'request_type_version_id' => $publishedId,
                    'payload_json' => json_encode(['item_name' => 'Laptop', 'quantity' => 1, 'business_reason' => 'Demo offline draft scenario', 'confidential_note' => 'Must never be stored offline'], JSON_THROW_ON_ERROR),
                    'display_snapshot_json' => json_encode(['item_name' => 'Laptop', 'quantity' => 1, 'business_reason' => 'Demo offline draft scenario'], JSON_THROW_ON_ERROR),
                    'payload_checksum' => hash('sha256', 'REQUEST_UI_DEMO:DEMO-DRAFT-001:1'),
                    'schema_version' => 1,
                    'source' => 'server_draft',
                    'created_by' => $actorId,
                    'created_at' => $now,
                ]);

                DB::table('request_instances')->where('id', $requestId)->update(['current_payload_revision_id' => $payloadId]);
            }
        });

        $this->command?->info('Request demo data ready. Open /admin/requests');
    }
}
