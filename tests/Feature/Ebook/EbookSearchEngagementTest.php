<?php

namespace Tests\Feature\Ebook;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookDocumentRecent;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookEngagementService;
use Modules\Ebook\Services\EbookSearchService;
use Tests\TestCase;

class EbookSearchEngagementTest extends TestCase
{
    use RefreshDatabase;

    private User $actingAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'ebook.ebook.disk' => 'local',
            'ebook.ebook.root' => 'ebooks',
            'ebook.ebook.search.max_documents' => 50,
            'ebook.ebook.search.max_file_kb' => 64,
            'ebook.ebook.search.max_total_kb' => 256,
            'ebook.ebook.recent_limit' => 2,
        ]);

        $this->actingAdmin = $this->user('acting@example.test');
        $this->actingAs($this->actingAdmin, 'admin');
    }

    public function test_search_finds_title_filename_and_description_metadata(): void
    {
        $document = $this->document('Livewire Upload', 'livewire-upload.md', 'Hướng dẫn upload component', 'body');

        $results = app(EbookSearchService::class)->search('Livewire');

        $this->assertSame($document->id, $results->first()['id']);
        $this->assertContains('title', $results->first()['matched']);
        $this->assertContains('filename', $results->first()['matched']);
    }

    public function test_search_finds_markdown_content(): void
    {
        $document = $this->document('Guide', 'guide.md', null, "# Guide\n\nQueue worker deployment with PM2");

        $results = app(EbookSearchService::class)->search('PM2');

        $this->assertSame($document->id, $results->first()['id']);
        $this->assertContains('content', $results->first()['matched']);
        $this->assertStringContainsString('PM2', $results->first()['snippet']);
    }

    public function test_favorite_toggle_changes_global_document_flag(): void
    {
        $document = $this->document('Guide', 'guide.md', null, 'body');
        $service = app(EbookEngagementService::class);

        $this->assertTrue($service->toggleFavorite((int) $document->id)->is_favorite);
        $this->assertFalse($service->toggleFavorite((int) $document->id)->is_favorite);
    }

    public function test_recent_documents_are_scoped_per_admin(): void
    {
        $adminA = $this->user('a@example.test');
        $adminB = $this->user('b@example.test');
        $document = $this->document('Guide', 'guide.md', null, 'body');
        $service = app(EbookEngagementService::class);

        $service->recordRecent((int) $adminA->id, (int) $document->id);

        $this->assertCount(1, $service->recents((int) $adminA->id));
        $this->assertCount(0, $service->recents((int) $adminB->id));
    }

    public function test_recent_history_is_bounded_by_configured_limit(): void
    {
        $admin = $this->user('admin@example.test');
        $service = app(EbookEngagementService::class);
        $documents = [
            $this->document('One', 'one.md', null, 'one'),
            $this->document('Two', 'two.md', null, 'two'),
            $this->document('Three', 'three.md', null, 'three'),
        ];

        foreach ($documents as $document) {
            $service->recordRecent((int) $admin->id, (int) $document->id);
        }

        $this->assertSame(2, EbookDocumentRecent::query()->where('user_id', $admin->id)->count());
        $this->assertCount(2, $service->recents((int) $admin->id, 10));
    }

    private function document(string $title, string $fileName, ?string $description, string $content): EbookDocument
    {
        $folder = EbookFolder::query()->firstOrCreate([
            'parent_id' => null,
            'slug' => 'docs',
        ], [
            'name' => 'Docs',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $path = 'ebooks/docs/'.$fileName;
        Storage::disk('local')->put($path, $content);

        $document = EbookDocument::query()->create([
            'folder_id' => $folder->id,
            'title' => $title,
            'slug' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'file_path' => $path,
            'source_type' => 'file',
            'description' => $description,
            'sort_order' => 0,
            'is_active' => true,
            'is_favorite' => false,
            'content_hash' => hash('sha256', $content),
        ]);

        $document->viewers()->sync(User::query()->pluck('id')->all());

        return $document;
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'Admin Test',
            'email' => $email,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
    }
}
