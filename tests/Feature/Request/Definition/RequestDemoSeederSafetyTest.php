<?php

namespace Tests\Feature\Request\Definition;

use App\Models\User;
use Database\Seeders\RequestDemoSeeder;
use Database\Seeders\RequestE2EDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RequestDemoSeederSafetyTest extends RequestDefinitionTestCase
{
    public function test_rerun_preserves_an_existing_demo_definition_and_its_version_history(): void
    {
        User::factory()->create(['email' => 'tungocvan@gmail.com', 'is_active' => true]);
        User::factory()->create(['email' => 'demo@website.test', 'is_active' => true]);

        $this->seed(RequestDemoSeeder::class);

        $type = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first();
        $publishedId = (int) $type->current_published_version_id;
        $draftId = (int) $type->active_draft_version_id;
        $this->assertSame(
            ['fixed_users', 'fixed_users'],
            DB::table('request_stage_definitions')->whereIn('request_type_version_id', [$publishedId, $draftId])->orderBy('id')->pluck('resolver_key')->all(),
        );

        DB::table('request_type_versions')->where('id', $publishedId)->update(['status' => 'superseded']);
        DB::table('request_type_versions')->where('id', $draftId)->update([
            'status' => 'published',
            'title' => 'Phiên bản do quản trị viên phát hành',
            'published_at' => now('UTC'),
        ]);
        DB::table('request_types')->where('id', $type->id)->update([
            'current_published_version_id' => $draftId,
            'active_draft_version_id' => null,
            'lock_version' => 99,
        ]);

        $beforeType = (array) DB::table('request_types')->where('id', $type->id)->first();
        $beforeVersions = DB::table('request_type_versions')->where('request_type_id', $type->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $beforeAudiences = DB::table('request_type_audiences')->whereIn('request_type_version_id', [$publishedId, $draftId])->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $beforeStages = DB::table('request_stage_definitions')->whereIn('request_type_version_id', [$publishedId, $draftId])->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->seed(RequestDemoSeeder::class);

        $this->assertSame($beforeType, (array) DB::table('request_types')->where('id', $type->id)->first());
        $this->assertSame($beforeVersions, DB::table('request_type_versions')->where('request_type_id', $type->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeAudiences, DB::table('request_type_audiences')->whereIn('request_type_version_id', [$publishedId, $draftId])->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeStages, DB::table('request_stage_definitions')->whereIn('request_type_version_id', [$publishedId, $draftId])->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame(1, DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->count());
    }

    public function test_e2e_seeder_does_not_reconfigure_a_demo_definition_after_admin_edits(): void
    {
        User::factory()->create(['email' => 'tungocvan@gmail.com', 'is_active' => true]);
        User::factory()->create(['email' => 'demo@website.test', 'is_active' => true]);

        $this->seed(RequestE2EDemoSeeder::class);

        $type = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first();
        $currentVersionId = (int) $type->current_published_version_id;
        DB::table('request_type_versions')->where('id', $currentVersionId)->update(['title' => 'Tiêu đề do quản trị viên chỉnh sửa']);
        DB::table('request_types')->where('id', $type->id)->update([
            'available_from' => '2026-01-01 00:00:00',
            'available_until' => '2027-01-01 00:00:00',
            'lock_version' => 2,
        ]);

        $beforeType = (array) DB::table('request_types')->where('id', $type->id)->first();
        $beforeVersions = DB::table('request_type_versions')->where('request_type_id', $type->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $beforeAudiences = DB::table('request_type_audiences')->whereIn('request_type_version_id', array_column($beforeVersions, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $beforeStages = DB::table('request_stage_definitions')->whereIn('request_type_version_id', array_column($beforeVersions, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->seed(RequestE2EDemoSeeder::class);

        $this->assertSame($beforeType, (array) DB::table('request_types')->where('id', $type->id)->first());
        $this->assertSame($beforeVersions, DB::table('request_type_versions')->where('request_type_id', $type->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeAudiences, DB::table('request_type_audiences')->whereIn('request_type_version_id', array_column($beforeVersions, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeStages, DB::table('request_stage_definitions')->whereIn('request_type_version_id', array_column($beforeVersions, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_e2e_reset_command_resolves_the_root_seeder_namespace(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/ResetRequestE2EDemo.php'));

        $this->assertStringContainsString('use Database\\Seeders\\RequestE2EDemoSeeder;', $command);
        $this->assertStringNotContainsString('use Modules\\Request\\Database\\Seeders\\RequestE2EDemoSeeder;', $command);
        $this->assertStringContainsString('{--rebuild', $command);
        $this->assertStringContainsString('normalizeLocalRequestStoragePermissions', $command);
        $this->assertStringContainsString("config('request.files.local_owner', 'www-data')", $command);
        $this->assertStringContainsString('02770', $command);
    }

    public function test_rebuild_command_creates_a_complete_request_ui_matrix(): void
    {
        $superAdmin = User::factory()->create(['email' => 'tungocvan@gmail.com', 'is_active' => true]);
        config()->set('request.files.disk', 'local');
        Storage::fake('local');

        $this->assertSame(0, Artisan::call('request:e2e-reset', ['--rebuild' => true]));

        $requesterId = (int) DB::table('users')->where('email', 'tungocvan1@gmail.com')->value('id');
        $approverId = (int) DB::table('users')->where('email', 'vhdtshop@gmail.com')->value('id');
        $this->assertNotSame($superAdmin->id, $requesterId);
        $this->assertSame([
            'approved' => 1,
            'cancelled' => 1,
            'draft' => 1,
            'pending' => 5,
            'rejected' => 1,
            'returned' => 1,
        ], DB::table('request_instances')->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->orderBy('status')->pluck('aggregate', 'status')->map(fn ($count) => (int) $count)->all());
        $this->assertSame(10, DB::table('request_instances')->where('requester_id', $requesterId)->count());
        $this->assertSame(4, DB::table('request_tasks')->where('assignee_user_id', $approverId)->where('status', 'active')->count());
        $this->assertSame(3, DB::table('request_tasks')->where('assignee_user_id', $approverId)->whereIn('status', ['approved', 'rejected', 'returned'])->count());
        $this->assertSame(2, DB::table('request_tasks')->whereNotNull('overdue_at')->count());
        $this->assertSame(1, DB::table('request_tasks')->whereNotNull('suspended_at')->count());
        $this->assertSame(1, DB::table('request_runs')->where('status', 'failed_activation')->count());
        $this->assertSame(1, DB::table('request_outbox_messages')->whereNotNull('failed_at')->count());
        $this->assertSame(1, DB::table('request_export_jobs')->where('status', 'failed')->count());
        $this->assertSame(2, DB::table('request_comments')->count());
        $this->assertSame(1, DB::table('request_attachments')->count());
        $attachment = DB::table('request_attachments')->first();
        Storage::disk($attachment->storage_disk)->assertExists($attachment->storage_path);
        $this->assertSame(1, DB::table('request_type_versions')->where('status', 'published')->count());
        $this->assertGreaterThan(0, DB::table('request_type_versions')->whereNotNull('created_from_version_id')->count());
        $this->assertSame(5, DB::table('request_types')->count());

        $this->assertSame(0, Artisan::call('request:e2e-reset', ['--rebuild' => true]));
        $this->assertSame(10, DB::table('request_instances')->count());
        $this->assertSame(5, DB::table('request_types')->count());
        $this->assertSame(2, DB::table('request_comments')->count());
        $this->assertSame(1, DB::table('request_attachments')->count());
    }
}
