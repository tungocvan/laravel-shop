<?php

namespace Tests\Feature\Request\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Request\Database\Seeders\RequestDemoSeeder;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Tests\TestCase;

class RequestDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--path' => 'Modules/Request/database/migrations',
            '--force' => true,
        ]);
    }

    public function test_demo_seeder_is_noop_when_request_environment_gate_is_disabled(): void
    {
        config([
            'request.settings.demo_seeders_enabled' => false,
            'request.settings.starter_templates_enabled' => true,
            'request.settings.starter_template_actor_id' => $this->user('Demo Actor'),
            'request.settings.starter_template_approver_id' => $this->user('Demo Approver'),
        ]);

        app(RequestDemoSeeder::class)->run();

        $this->assertDatabaseMissing('request_groups', ['code' => 'STARTER']);
        $this->assertSame(0, RequestType::query()->count());
    }

    public function test_demo_seeder_runs_idempotently_when_request_environment_gate_is_enabled(): void
    {
        config([
            'request.settings.demo_seeders_enabled' => true,
            'request.settings.starter_templates_enabled' => true,
            'request.settings.starter_template_actor_id' => $this->user('Demo Actor'),
            'request.settings.starter_template_approver_id' => $this->user('Demo Approver'),
        ]);

        app(RequestDemoSeeder::class)->run();
        app(RequestDemoSeeder::class)->run();

        $this->assertSame(1, RequestGroup::query()->where('code', 'STARTER')->count());
        $this->assertSame(1, RequestType::query()->where('code', 'GENERAL_APPROVAL')->count());
        $this->assertSame(1, RequestType::query()->where('code', 'EXPENSE_REIMBURSEMENT')->count());
    }

    private function user(string $name): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => uniqid('request-demo-', true).'@example.test',
            'is_active' => true,
            'password' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
