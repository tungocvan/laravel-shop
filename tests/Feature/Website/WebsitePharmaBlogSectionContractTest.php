<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsitePharmaBlogSectionContractTest extends TestCase
{
    public function test_pharma_blog_builder_is_template_scoped_and_supports_duplicates(): void
    {
        $manager = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $builder = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString('website.pharma_homepage.blog_sections', $manager);
        $this->assertStringContainsString('duplicatePharmaBlogSection', $manager);
        $this->assertStringContainsString('removePharmaBlogSection', $manager);
        $this->assertStringContainsString('savePharmaBlogSections', $manager);
        $this->assertStringContainsString('published_posts_count', $builder);
        $this->assertStringContainsString('pharmaBlogSections.', $builder);
        $this->assertStringContainsString('PharmaBlogSection::class', $home);
    }

    public function test_pharma_blog_public_query_only_renders_published_posts(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Home/PharmaBlogSection.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-blog-section.blade.php'));

        $this->assertStringContainsString("where('status', 'published')", $component);
        $this->assertStringContainsString("where('type', 'post')", $component);
        $this->assertStringContainsString("route('blog.detail', \$post->slug)", $view);
        $this->assertStringContainsString('wire:click="loadMore"', $view);
    }
}
