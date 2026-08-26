<?php

namespace Tests\Feature\Request\Definition;

use App\Models\User;
use Database\Seeders\RequestDemoSeeder;
use Database\Seeders\RequestE2EDemoSeeder;
use Illuminate\Support\Facades\DB;

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
    }
}
