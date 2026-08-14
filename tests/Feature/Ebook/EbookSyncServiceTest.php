<?php

namespace Tests\Feature\Ebook;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookSyncService;
use Tests\TestCase;

class EbookSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['ebook.ebook.disk' => 'local', 'ebook.ebook.root' => 'ebooks']);

        Schema::dropIfExists('ebook_document_recents');
        Schema::dropIfExists('ebook_documents');
        Schema::dropIfExists('ebook_folders');
        Schema::create('ebook_folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['parent_id', 'slug']);
        });
        Schema::create('ebook_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id');
            $table->string('title');
            $table->string('slug');
            $table->string('file_name');
            $table->string('file_path')->unique();
            $table->string('source_type')->default('file');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->char('content_hash', 64)->nullable();
            $table->unsignedBigInteger('file_mtime')->nullable();
            $table->timestamps();
            $table->unique(['folder_id', 'slug']);
        });
    }

    public function test_preview_detects_new_folder_and_file_without_mutating_database(): void
    {
        Storage::disk('local')->makeDirectory('ebooks/laravel');
        Storage::disk('local')->put('ebooks/laravel/guide.md', "# Guide\n\nBody");

        $plan = app(EbookSyncService::class)->preview();

        $this->assertSame(1, $plan['summary']['new']);
        $this->assertCount(1, $plan['new_folders']);
        $this->assertCount(1, $plan['new_files']);
        $this->assertDatabaseCount('ebook_folders', 0);
        $this->assertDatabaseCount('ebook_documents', 0);
    }

    public function test_apply_confirmed_creates_folder_and_document_metadata(): void
    {
        Storage::disk('local')->makeDirectory('ebooks/laravel');
        Storage::disk('local')->put('ebooks/laravel/guide.md', "# Guide Title\n\nBody");
        $service = app(EbookSyncService::class);
        $plan = $service->preview();
        $keys = [
            $plan['new_folders'][0]['key'],
            $plan['new_files'][0]['key'],
        ];

        $report = $service->applyConfirmed($keys);

        $this->assertCount(2, $report['applied']);
        $this->assertDatabaseHas('ebook_folders', ['slug' => 'laravel']);
        $this->assertDatabaseHas('ebook_documents', [
            'title' => 'Guide Title',
            'file_path' => 'ebooks/laravel/guide.md',
        ]);
        $this->assertSame(0, $report['preview']['summary']['new']);
    }

    public function test_changed_file_updates_hash_only_after_confirmed_apply(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        Storage::disk('local')->put('ebooks/laravel/guide.md', 'new content');
        $document = $this->document($folder, 'Guide', 'guide', 'ebooks/laravel/guide.md', hash('sha256', 'old content'));
        $service = app(EbookSyncService::class);
        $plan = $service->preview();

        $this->assertCount(1, $plan['changed']);
        $this->assertSame(hash('sha256', 'old content'), $document->fresh()->content_hash);

        $service->applyConfirmed([$plan['changed'][0]['key']]);

        $this->assertSame(hash('sha256', 'new content'), $document->fresh()->content_hash);
    }

    public function test_missing_file_is_reported_but_never_auto_deleted(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $document = $this->document($folder, 'Guide', 'guide', 'ebooks/laravel/guide.md', hash('sha256', 'body'));

        $plan = app(EbookSyncService::class)->preview();

        $this->assertCount(1, $plan['missing_files']);
        $this->assertDatabaseHas('ebook_documents', ['id' => $document->id]);
    }

    public function test_unique_hash_match_is_classified_and_applied_as_move(): void
    {
        $source = $this->folder('Laravel', 'laravel');
        $target = $this->folder('PHP', 'php');
        $hash = hash('sha256', 'same body');
        $document = $this->document($source, 'Guide', 'guide', 'ebooks/laravel/guide.md', $hash);
        Storage::disk('local')->makeDirectory('ebooks/php');
        Storage::disk('local')->put('ebooks/php/renamed.md', 'same body');
        $service = app(EbookSyncService::class);
        $plan = $service->preview();

        $this->assertCount(1, $plan['moves']);
        $this->assertCount(0, $plan['missing_files']);
        $this->assertCount(0, $plan['new_files']);

        $service->applyConfirmed([$plan['moves'][0]['key']]);
        $document->refresh();

        $this->assertSame($target->id, $document->folder_id);
        $this->assertSame('ebooks/php/renamed.md', $document->file_path);
        $this->assertSame('renamed.md', $document->file_name);
    }

    public function test_ambiguous_hash_match_never_becomes_automatic_move(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $hash = hash('sha256', 'duplicate body');
        $this->document($folder, 'Guide', 'guide', 'ebooks/laravel/guide.md', $hash);
        Storage::disk('local')->makeDirectory('ebooks/php');
        Storage::disk('local')->put('ebooks/php/a.md', 'duplicate body');
        Storage::disk('local')->put('ebooks/php/b.md', 'duplicate body');

        $plan = app(EbookSyncService::class)->preview();

        $this->assertCount(0, $plan['moves']);
        $this->assertCount(1, $plan['ambiguous']);
        $this->assertCount(1, $plan['missing_files']);
        $this->assertCount(2, $plan['new_files']);
    }

    public function test_apply_revalidates_and_skips_stale_preview_key(): void
    {
        Storage::disk('local')->makeDirectory('ebooks/laravel');
        Storage::disk('local')->put('ebooks/laravel/guide.md', '# Guide');
        $service = app(EbookSyncService::class);
        $plan = $service->preview();
        $key = $plan['new_files'][0]['key'];
        Storage::disk('local')->delete('ebooks/laravel/guide.md');

        $report = $service->applyConfirmed([$key]);

        $this->assertCount(0, $report['applied']);
        $this->assertCount(1, $report['skipped']);
        $this->assertDatabaseCount('ebook_documents', 0);
    }

    private function folder(string $name, string $slug): EbookFolder
    {
        Storage::disk('local')->makeDirectory('ebooks/'.$slug);
        return EbookFolder::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    private function document(EbookFolder $folder, string $title, string $slug, string $path, string $hash): EbookDocument
    {
        return EbookDocument::query()->create([
            'folder_id' => $folder->id,
            'title' => $title,
            'slug' => $slug,
            'file_name' => basename($path),
            'file_path' => $path,
            'source_type' => 'file',
            'sort_order' => 0,
            'is_active' => true,
            'is_favorite' => false,
            'content_hash' => $hash,
            'file_mtime' => 0,
        ]);
    }
}
