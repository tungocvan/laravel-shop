<?php

return [
    'page_sizes' => [10, 25, 50, 100],
    'default_page_size' => 25,
    'max_page_size' => 100,
    'request_number_prefix' => 'REQ',
    'max_stage_count' => 20,
    'max_candidates_per_stage' => 100,
    'max_sla_duration_minutes' => 525600,
    'starter_templates_enabled' => (bool) env('REQUEST_STARTER_TEMPLATES_ENABLED', false),
    'starter_template_actor_id' => (int) env('REQUEST_STARTER_TEMPLATE_ACTOR_ID', 0),
    'starter_template_approver_id' => (int) env('REQUEST_STARTER_TEMPLATE_APPROVER_ID', 0),
];
