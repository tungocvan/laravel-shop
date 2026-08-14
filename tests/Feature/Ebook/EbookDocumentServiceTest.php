<?php

namespace Tests\Feature\Ebook;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookDocumentService;
use Tests\TestCase;

class EbookDocumentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['ebook.ebook.disk' => 'local', 'ebook.ebook.root' => 'ebooks']);
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

    public function test_create_persists_file_and_metadata(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $document = app(EbookDocumentService::class)->create([
            'folder_id' => $folder->id,
            'title' => 'Livewire Upload',
            'content' => "# Livewire Upload\n\nHello",
        ]);

        $this->assertSame('ebooks/laravel/livewire-upload.md', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame(hash('sha256', "# Livewire Upload\n\nHello"), $document->content_hash);
    }

    public function test_upload_extracts_first_h1_title(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $file = UploadedFile::fake()->createWithContent('guide.md', "# Guide Title\n\nBody");

        $document = app(EbookDocumentService::class)->upload((int) $folder->id, $file);

        $this->assertSame('Guide Title', $document->title);
        $this->assertSame('guide.md', $document->file_name);
    }

    public function test_duplicate_destination_is_rejected(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $service = app(EbookDocumentService::class);
        $service->create(['folder_id' => $folder->id, 'title' => 'Guide', 'content' => 'one']);

        $this->expectException(ValidationException::class);
        $service->create(['folder_id' => $folder->id, 'title' => 'Guide', 'content' => 'two']);
    }

    public function test_external_change_conflict_prevents_overwrite(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $service = app(EbookDocumentService::class);
        $document = $service->create(['folder_id' => $folder->id, 'title' => 'Guide', 'content' => 'original']);
        Storage::disk('local')->put($document->file_path, 'external edit');

        $this->expectException(ValidationException::class);
        $service->update((int) $document->id, [
            'content' => 'admin edit',
            'expected_hash' => $document->content_hash,
        ]);
    }

    public function test_move_changes_path_and_keeps_content(): void
    {
        $source = $this->folder('Laravel', 'laravel');
        $target = $this->folder('PHP', 'php');
        $service = app(EbookDocumentService::class);
        $document = $service->create(['folder_id' => $source->id, 'title' => 'Guide', 'content' => 'body']);

        $updated = $service->update((int) $document->id, [
            'folder_id' => $target->id,
            'expected_hash' => $document->content_hash,
        ]);

        $this->assertSame('ebooks/php/guide.md', $updated->file_path);
        Storage::disk('local')->assertMissing('ebooks/laravel/guide.md');
        Storage::disk('local')->assertExists('ebooks/php/guide.md');
        $this->assertSame('body', $service->content($updated));
    }

    public function test_delete_removes_file_and_metadata(): void
    {
        $folder = $this->folder('Laravel', 'laravel');
        $service = app(EbookDocumentService::class);
        $document = $service->create(['folder_id' => $folder->id, 'title' => 'Guide', 'content' => 'body']);
        $path = $document->file_path;

        $service->delete((int) $document->id);

        Storage::disk('local')->assertMissing($path);
        $this->assertFalse(EbookDocument::query()->whereKey($document->id)->exists());
    }

    private function folder(string $name, string $slug): EbookFolder
    {
        return EbookFolder::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }
}
