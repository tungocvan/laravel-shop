<?php

return [
    '2026_09_01_000001_create_request_definition_tables' => ['tables' => ['request_groups', 'request_types', 'request_type_versions', 'request_type_audiences', 'request_stage_definitions']],
    '2026_09_01_000002_add_request_type_version_pointers' => ['columns' => ['request_types' => ['current_published_version_id', 'active_draft_version_id']]],
    '2026_09_01_000003_create_request_core_reliability_tables' => ['tables' => ['request_audit_events', 'request_outbox_messages', 'request_idempotency_keys']],
    '2026_09_01_000004_create_request_runtime_tables' => ['tables' => ['request_instances', 'request_payload_revisions', 'request_runs']],
    '2026_09_01_000005_create_request_task_tables' => ['tables' => ['request_tasks', 'request_task_candidates', 'request_decisions']],
    '2026_09_01_000006_add_request_runtime_pointers' => ['columns' => ['request_instances' => ['current_payload_revision_id', 'current_run_id'], 'request_audit_events' => ['request_instance_id']]],
    '2026_09_01_000007_create_request_collaboration_delivery_tables' => ['tables' => ['request_comments', 'request_attachments', 'request_export_jobs', 'request_notification_deliveries']],
    '2026_09_01_000008_add_request_stage_sla_fields' => ['columns' => ['request_stage_definitions' => ['sla_minutes', 'warning_minutes_before', 'grace_minutes', 'timeout_action'], 'request_tasks' => ['sla_snapshot_json', 'warning_at', 'due_at', 'grace_expires_at', 'overdue_at', 'suspended_at']]],
    '2026_09_01_000009_add_request_stage_email_notification_fields' => ['columns' => ['request_stage_definitions' => ['email_on_assignment', 'email_on_decision', 'email_on_sla_warning']]],
];
