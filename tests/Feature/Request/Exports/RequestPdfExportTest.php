<?php

namespace Tests\Feature\Request\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Request\Application\Services\GenerateRequestExport;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestExportJob;
use Tests\TestCase;

class RequestPdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
        Storage::fake('local');
        config(['request.files.disk' => 'local']);
    }

    public function test_single_request_pdf_is_private_bounded_and_remote_content_is_disabled(): void
    {
        $request = InternalRequest::factory()->create([
            'title_snapshot' => '<img src="https://example.test/tracker.png"><script>alert(1)</script>Đề nghị an toàn',
        ]);
        $export = RequestExportJob::factory()->create([
            'requested_by' => 1,
            'filter_snapshot_json' => ['request_public_id' => $request->public_id],
            'field_snapshot_json' => ['request_number', 'title', 'status'],
            'authorization_scope_json' => ['user_id' => 1, 'view_all' => true, 'view_own' => false, 'view_participant' => false],
            'format' => 'pdf',
            'status' => ExportStatus::Pending,
            'row_count' => 1,
        ]);

        $generated = app(GenerateRequestExport::class)->handle($export);

        $this->assertSame(ExportStatus::Ready, $generated->status);
        $this->assertSame(1, $generated->row_count);
        $this->assertStringStartsWith('request/exports/'.$generated->public_id.'/', $generated->storage_path);
        Storage::disk('local')->assertExists($generated->storage_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($generated->storage_path));

        $generator = file_get_contents(base_path('Modules/Request/Application/Services/GenerateRequestExport.php'));
        $template = file_get_contents(base_path('Modules/Request/resources/views/exports/single-request-pdf.blade.php'));
        $this->assertStringContainsString("setOption('isRemoteEnabled', false)", $generator);
        $this->assertStringContainsString("setOption('isPhpEnabled', false)", $generator);
        $this->assertStringNotContainsString('{!!', $template);
    }
}
