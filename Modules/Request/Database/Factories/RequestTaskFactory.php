<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestStageDefinition;
use Modules\Request\Models\RequestTask;

class RequestTaskFactory extends Factory
{
    protected $model = RequestTask::class;

    public function definition(): array
    {
        return ['request_run_id' => RequestRun::factory(), 'request_stage_definition_id' => RequestStageDefinition::factory(), 'stage_key_snapshot' => 'approval', 'stage_name_snapshot' => 'Approval', 'stage_position' => 1, 'stage_mode' => StageMode::Single, 'status' => TaskStatus::Active, 'assignee_user_id' => 1, 'resolver_key_snapshot' => 'fixed_users', 'resolver_source_snapshot_json' => ['source' => 'fixed_user'], 'activated_at' => now('UTC'), 'lock_version' => 1];
    }
}
