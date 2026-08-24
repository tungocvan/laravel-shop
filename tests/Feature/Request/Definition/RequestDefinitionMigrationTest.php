<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Support\Facades\Schema;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestType;

class RequestDefinitionMigrationTest extends RequestDefinitionTestCase
{
    public function test_mr_03_adds_runtime_base_but_not_submission_or_task_tables(): void
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
            $this->assertFalse(Schema::hasTable($mr04Table), "MR-04 table {$mr04Table} must not exist.");
        }

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

        $this->assertNotNull($group->public_id);
        $this->assertSame($group->id, $type->request_group_id);
        $this->assertSame($request->request_type_version_id, $revision->request_type_version_id);
        $this->assertSame($request->request_type_version_id, $run->request_type_version_id);
    }
}
