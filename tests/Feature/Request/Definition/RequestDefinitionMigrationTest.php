<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Support\Facades\Schema;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Request\Models\RequestComment;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestNotificationDelivery;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestTask;
use Modules\Request\Models\RequestType;

class RequestDefinitionMigrationTest extends RequestDefinitionTestCase
{
    public function test_mr_04_adds_task_decision_tables_and_runtime_pointers(): void
    {
        foreach (['request_groups', 'request_types', 'request_type_versions', 'request_type_audiences', 'request_stage_definitions', 'request_audit_events', 'request_outbox_messages', 'request_idempotency_keys'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing {$table}.");
        }

        foreach (['request_instances', 'request_payload_revisions', 'request_runs'] as $mr03Table) {
            $this->assertTrue(Schema::hasTable($mr03Table), "MR-03 table {$mr03Table} must exist.");
        }

        $this->assertTrue(Schema::hasColumns('request_instances', ['public_id', 'request_number', 'request_type_id', 'request_type_version_id', 'requester_id', 'status', 'lock_version']));
        $this->assertTrue(Schema::hasColumns('request_payload_revisions', ['request_instance_id', 'revision_number', 'request_type_version_id', 'payload_json', 'payload_checksum', 'source']));
        $this->assertTrue(Schema::hasColumns('request_runs', ['request_instance_id', 'sequence_number', 'request_type_version_id', 'request_payload_revision_id', 'status', 'lock_version']));

        foreach (['request_tasks', 'request_task_candidates', 'request_decisions'] as $mr04Table) {
            $this->assertTrue(Schema::hasTable($mr04Table), "MR-04 table {$mr04Table} must exist.");
        }

        $this->assertTrue(Schema::hasColumns('request_instances', ['current_payload_revision_id', 'current_run_id']));
        $this->assertTrue(Schema::hasColumns('request_audit_events', ['request_instance_id']));
        $this->assertTrue(Schema::hasColumns('request_tasks', ['request_run_id', 'request_stage_definition_id', 'stage_position', 'stage_mode', 'assignee_user_id', 'lock_version']));
        $this->assertTrue(Schema::hasColumns('request_task_candidates', ['request_task_id', 'user_id', 'source_type', 'user_snapshot_json', 'is_effective']));
        $this->assertTrue(Schema::hasColumns('request_decisions', ['request_task_id', 'request_run_id', 'request_instance_id', 'decision', 'actor_user_id', 'idempotency_key_hash']));

        $this->assertTrue(Schema::hasColumns('request_types', ['current_published_version_id', 'active_draft_version_id', 'lock_version']));
    }

    public function test_definition_and_runtime_factories_are_autoloadable(): void
    {
        $group = RequestGroup::factory()->create();
        $type = RequestType::factory()->for($group, 'group')->create();
        $request = InternalRequest::factory()->create();
        $revision = RequestPayloadRevision::factory()->create(['request_instance_id' => $request->id]);
        $run = RequestRun::factory()->create([
            'request_instance_id' => $request->id,
            'request_payload_revision_id' => $revision->id,
        ]);
        $task = RequestTask::factory()->create(['request_run_id' => $run->id]);

        $this->assertNotNull($group->public_id);
        $this->assertSame($group->id, $type->request_group_id);
        $this->assertSame($request->request_type_version_id, $revision->request_type_version_id);
        $this->assertSame($request->request_type_version_id, $run->request_type_version_id);
        $this->assertSame($run->id, $task->request_run_id);
    }

    public function test_mr_06_adds_collaboration_and_delivery_capability_tables(): void
    {
        foreach (['request_comments', 'request_attachments', 'request_export_jobs', 'request_notification_deliveries'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing MR-06 table {$table}.");
        }

        $this->assertTrue(Schema::hasColumns('request_comments', ['public_id', 'request_instance_id', 'request_run_id', 'author_id', 'body', 'body_format', 'redacted_at']));
        $this->assertTrue(Schema::hasColumns('request_attachments', ['public_id', 'request_instance_id', 'request_comment_id', 'payload_field_key', 'storage_disk', 'storage_path', 'checksum', 'classification', 'scan_status', 'removed_at']));
        $this->assertTrue(Schema::hasColumns('request_export_jobs', ['public_id', 'requested_by', 'filter_snapshot_json', 'field_snapshot_json', 'authorization_scope_json', 'status', 'idempotency_key_hash']));
        $this->assertTrue(Schema::hasColumns('request_notification_deliveries', ['public_id', 'logical_key', 'channel', 'recipient_id', 'template_key', 'status', 'attempt_count']));
    }

    public function test_mr_06_factories_are_autoloadable(): void
    {
        $request = InternalRequest::factory()->create();
        $comment = RequestComment::factory()->create(['request_instance_id' => $request->id]);
        $attachment = RequestAttachment::factory()->create(['request_instance_id' => $request->id]);

        $this->assertSame($request->id, $comment->request_instance_id);
        $this->assertSame($request->id, $attachment->request_instance_id);
        $this->assertNotNull(RequestExportJob::factory()->create()->public_id);
        $this->assertNotNull(RequestNotificationDelivery::factory()->create()->public_id);
    }

    public function test_mr_06_migration_rolls_back_in_dependency_order_and_migrates_again(): void
    {
        $migration = require base_path('Modules/Request/database/migrations/2026_09_01_000007_create_request_collaboration_delivery_tables.php');
        $migration->down();

        foreach (['request_comments', 'request_attachments', 'request_export_jobs', 'request_notification_deliveries'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "MR-06 rollback left {$table} behind.");
        }

        $migration->up();
        foreach (['request_comments', 'request_attachments', 'request_export_jobs', 'request_notification_deliveries'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "MR-06 remigration did not restore {$table}.");
        }
    }
}
