<?php

namespace Tests\Feature\Request\Draft;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\PublishTypeVersion;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Models\RequestType;
use Tests\TestCase;

abstract class RequestDraftTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }

    protected function activeUser(string $name = 'Requester'): int
    {
        return (int) DB::table('users')->insertGetId(['name' => $name, 'email' => uniqid().'@example.test', 'is_active' => true, 'password' => null, 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function adminRole(string $name = 'Request role'): int
    {
        return (int) DB::table('roles')->insertGetId(['name' => $name, 'guard_name' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function publishedType(int $actorId, array $schema, ?int $audienceUserId = null): RequestType
    {
        $group = app(CreateRequestGroup::class)->handle(['code' => 'G'.uniqid(), 'name' => 'General'], $actorId);
        $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'T'.uniqid(), 'name' => 'Internal request'], $actorId);
        app(SaveTypeDraft::class)->handle($type, [
            'title' => 'Internal request',
            'form_schema_json' => $schema,
            'policy_json' => [],
            'presentation_json' => [],
            'audiences' => [['actor_type' => 'user', 'actor_id' => $audienceUserId ?? $actorId, 'capability' => 'create']],
            'stages' => [['stage_key' => 'approval', 'name' => 'Approval', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [$actorId]], 'allow_reassignment' => false]],
        ], $actorId, 1);
        app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, 2);

        return $type->refresh();
    }

    protected function simpleSchema(): array
    {
        return ['schema_version' => 1, 'sections' => [['key' => 'details', 'label' => 'Details', 'fields' => [['key' => 'subject', 'type' => 'text', 'label' => 'Subject', 'required' => true, 'validation' => ['max_length' => 100]]]]]];
    }
}
