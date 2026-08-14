<?php

namespace Tests\Feature\Ebook;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Services\MarkdownService;
use Tests\TestCase;

class EbookMarkdownServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'ebook.ebook.disk' => 'local',
            'ebook.ebook.root' => 'ebooks',
        ]);
    }

    public function test_render_strips_raw_html_and_unsafe_links(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            "# Guide\n\n<script>alert('xss')</script>\n\n[bad](javascript:alert(1))"
        );

        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertStringNotContainsString('javascript:', $result['html']);
    }

    public function test_render_generates_stable_unique_heading_ids_and_toc(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            "# Install\n\n## Setup\n\n## Setup"
        );

        $this->assertStringContainsString('<h1 id="install">', $result['html']);
        $this->assertStringContainsString('<h2 id="setup">', $result['html']);
        $this->assertStringContainsString('<h2 id="setup-2">', $result['html']);
        $this->assertSame(['install', 'setup', 'setup-2'], array_column($result['toc'], 'id'));
    }

    public function test_fenced_code_preserves_language_metadata(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            "```php\n<?php echo 'ok';\n```"
        );

        $this->assertStringContainsString('language-php', $result['html']);
    }

    public function test_fenced_code_is_decorated_with_copy_control(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            "```bash\nphp artisan test\n```"
        );

        $this->assertStringContainsString('ebook-code-block', $result['html']);
        $this->assertStringContainsString('Sao chép', $result['html']);
        $this->assertStringContainsString('navigator.clipboard.writeText', $result['html']);
        $this->assertStringContainsString('language-bash', $result['html']);
    }

    public function test_external_http_link_opens_safely_in_new_tab(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            '[Laravel](https://laravel.com/docs)'
        );

        $this->assertStringContainsString('target="_blank"', $result['html']);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result['html']);
        $this->assertStringContainsString('ebook-external-link', $result['html']);
        $this->assertStringContainsString('↗', $result['html']);
    }

    public function test_anchor_link_remains_internal(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            '[Setup](#setup)'
        );

        $this->assertStringContainsString('href="#setup"', $result['html']);
        $this->assertStringNotContainsString('target="_blank"', $result['html']);
    }

    public function test_relative_image_is_rewritten_to_protected_asset_route(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            '![Architecture](images/architecture.png)'
        );

        $this->assertStringContainsString(route('admin.ebook.asset', [
            'document' => 10,
            'path' => 'images/architecture.png',
        ]), $result['html']);
    }

    public function test_image_is_decorated_for_responsive_lightbox_and_caption(): void
    {
        $result = app(MarkdownService::class)->render(
            $this->document(),
            '![Architecture Diagram](images/architecture.png)'
        );

        $this->assertStringContainsString('cursor-zoom-in', $result['html']);
        $this->assertStringContainsString('loading="lazy"', $result['html']);
        $this->assertStringContainsString('x-data="{ open: false }"', $result['html']);
        $this->assertStringContainsString('Phóng to hình ảnh', $result['html']);
        $this->assertStringContainsString('Architecture Diagram', $result['html']);
    }

    public function test_asset_path_may_move_within_root_but_cannot_escape_root(): void
    {
        $service = app(MarkdownService::class);
        $document = $this->document();

        $this->assertSame(
            'ebooks/shared/image.png',
            $service->resolveAssetPath($document, '../shared/image.png')
        );

        $this->expectException(ValidationException::class);
        $service->resolveAssetPath($document, '../../../secret.png');
    }

    private function document(): EbookDocument
    {
        $document = new EbookDocument([
            'folder_id' => 1,
            'title' => 'Guide',
            'slug' => 'guide',
            'file_name' => 'guide.md',
            'file_path' => 'ebooks/laravel/guide.md',
            'source_type' => 'file',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $document->id = 10;

        return $document;
    }
}
