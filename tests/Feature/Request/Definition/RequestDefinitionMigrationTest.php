<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Support\Facades\Schema;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

class RequestDefinitionMigrationTest extends RequestDefinitionTestCase
{
    public function test_mr_02_creates_only_definition_and_core_reliability_tables(): void
    {
        foreach (['request_groups', 'request_types', 'request_type_versions', 'request_type_audiences', 'request_stage_definitions', 'request_audit_events', 'request_outbox_messages', 'request_idempotency_keys'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing {$table}.");
        }

        foreach (['request_instances', 'request_payload_revisions', 'request_runs', 'request_tasks', 'request_decisions'] as $mr03Table) {
            $this->assertFalse(Schema::hasTable($mr03Table), "MR-03 table {$mr03Table} must not exist.");
        }

        $this->assertTrue(Schema::hasColumns('request_types', ['current_published_version_id', 'active_draft_version_id', 'lock_version']));
    }

    public function test_definition_factories_are_autoloadable(): void
    {
        $group = RequestGroup::factory()->create();
        $type = RequestType::factory()->for($group, 'group')->create();

        $this->assertNotNull($group->public_id);
        $this->assertSame($group->id, $type->request_group_id);
    }
}
