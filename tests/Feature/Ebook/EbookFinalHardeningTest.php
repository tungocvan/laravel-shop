<?php

namespace Tests\Feature\Ebook;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Services\EbookAccessService;
use Modules\Ebook\Services\EbookSyncService;
use Modules\Ebook\Services\MarkdownService;
use Tests\TestCase;

class EbookFinalHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_new_document_grants_viewer_access_to_sync_actor(): void
    {
        Storage::fake('local');
        config(['ebook.ebook.disk' => 'local', 'ebook.ebook.root' => 'ebooks']);
        $user = $this->user('sync-actor@example.test');
        $this->actingAs($user, 'admin');

        Storage::disk('local')->makeDirectory('ebooks/synced');
        Storage::disk('local')->put('ebooks/synced/guide.md', "# Synced Guide\n\nBody");

        $service = app(EbookSyncService::class);
        $plan = $service->preview();
        $service->applyConfirmed([$plan['new_files'][0]['key']]);

        $document = EbookDocument::query()->where('file_path', 'ebooks/synced/guide.md')->firstOrFail();

        $this->assertTrue($document->viewers()->whereKey($user->id)->exists());
        $this->assertTrue(app(EbookAccessService::class)->canView($user, $document));
    }

    public function test_markdown_fenced_code_contains_real_syntax_tokens(): void
    {
        $rendered = app(MarkdownService::class)->renderPreview("```php\n<?php\nreturn 'ok'; // comment\n```\n");

        $this->assertStringContainsString('language-php', $rendered['html']);
        $this->assertStringContainsString('ebook-syntax-keyword', $rendered['html']);
        $this->assertStringContainsString('ebook-syntax-string', $rendered['html']);
        $this->assertStringContainsString('ebook-syntax-comment', $rendered['html']);
        $this->assertStringContainsString('text-fuchsia-300', $rendered['html']);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'Ebook Hardening',
            'email' => $email,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
    }
}
