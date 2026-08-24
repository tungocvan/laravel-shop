<?php

namespace Tests\Feature\Request\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Request\Application\Services\ExpireRequestArtifacts;
use Modules\Request\Application\Services\PlanRequestExport;
use Modules\Request\Application\Services\StartRequestExport;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Support\SpreadsheetCellSanitizer;
use Tests\TestCase;

class RequestExportGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
        Storage::fake('local');
        config(['request.files.disk' => 'local']);
    }

    public function test_spreadsheet_formula_vectors_are_neutralized(): void
    {
        $sanitizer = app(SpreadsheetCellSanitizer::class);

        foreach (['=1+1', '+CMD', '-10+20', '@SUM(A1)', "\t=1", "\r=1"] as $unsafe) {
            $this->assertSame("'".$unsafe, $sanitizer->sanitize($unsafe));
        }

        $this->assertSame('Văn bản bình thường', $sanitizer->sanitize('Văn bản bình thường'));
    }

    public function test_small_csv_export_is_private_idempotent_and_formula_safe(): void
    {
        InternalRequest::factory()->create([
            'requester_id' => 71,
            'title_snapshot' => '=HYPERLINK("https://example.test")',
        ]);

        $user = $this->user(71, ['request.export', 'request.instance.view-own']);
        $plan = app(PlanRequestExport::class)->plan($user, [], ['request_number', 'title']);
        $starter = app(StartRequestExport::class);

        $first = $starter->handle($user, $plan, 'csv', 'same-request');
        $second = $starter->handle($user, $plan, 'csv', 'same-request');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ExportStatus::Ready, $first->status);
        $this->assertSame(1, $first->row_count);
        $this->assertNotNull($first->expires_at);
        $this->assertStringStartsWith('request/exports/'.$first->public_id.'/', $first->storage_path);
        Storage::disk('local')->assertExists($first->storage_path);

        $contents = Storage::disk('local')->get($first->storage_path);
        $this->assertStringContainsString("'=HYPERLINK", $contents);
        $this->assertStringNotContainsString('payload_json', $contents);
    }

    public function test_expiry_is_bounded_and_removes_private_artifact(): void
    {
        $export = \Modules\Request\Models\RequestExportJob::factory()->create([
            'status' => ExportStatus::Ready,
            'storage_disk' => 'local',
            'storage_path' => 'request/exports/test/export.csv',
            'expires_at' => now()->subMinute(),
        ]);
        Storage::disk('local')->put($export->storage_path, 'private');

        $this->assertSame(1, app(ExpireRequestArtifacts::class)->handle(1));

        $export->refresh();
        $this->assertSame(ExportStatus::Expired, $export->status);
        $this->assertNull($export->storage_path);
        Storage::disk('local')->assertMissing('request/exports/test/export.csv');
    }

    private function user(int $id, array $permissions): object
    {
        return new class($id, $permissions)
        {
            public function __construct(private readonly int $id, private readonly array $permissions) {}

            public function getAuthIdentifier(): int
            {
                return $this->id;
            }

            public function checkPermissionTo(string $permission, string $guard): bool
            {
                return $guard === 'admin' && in_array($permission, $this->permissions, true);
            }
        };
    }
}
