<?php

namespace Tests\Unit\Post;

use Illuminate\Validation\ValidationException;
use Modules\Post\Services\PostService;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    public function test_normalize_per_page_accepts_only_allowed_values(): void
    {
        $service = new PostService();

        $this->assertSame(10, $service->normalizePerPage(10));
        $this->assertSame(25, $service->normalizePerPage('25'));
        $this->assertSame(10, $service->normalizePerPage(999));
        $this->assertSame(10, $service->normalizePerPage('all'));
    }

    public function test_normalize_slug_uses_name_when_slug_is_blank(): void
    {
        $service = new PostService();

        $this->assertSame('hello-world', $service->normalizeSlug('', 'Hello World'));
        $this->assertSame('custom-slug', $service->normalizeSlug('Custom Slug', 'Ignored'));
    }

    public function test_normalize_slug_rejects_empty_input(): void
    {
        $this->expectException(ValidationException::class);

        (new PostService())->normalizeSlug('', '');
    }

    public function test_normalize_tag_names_trims_deduplicates_and_removes_empty_values(): void
    {
        $service = new PostService();

        $this->assertSame(
            ['Laravel', 'Livewire'],
            $service->normalizeTagNames(' Laravel, Livewire, laravel, , ')
        );
    }
}
