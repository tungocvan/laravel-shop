<?php

namespace Tests\Feature\Website;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Modules\Website\Models\WebsitePage;
use Tests\TestCase;

class WebsiteContentSchemaTest extends TestCase
{
    private string $migrationPath = 'Modules/Website/database/migrations/2026_08_11_150000_create_website_content_structure.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTables();
        Schema::enableForeignKeyConstraints();
        $this->migration()->up();
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function test_fresh_schema_exposes_required_columns_indexes_and_foreign_keys(): void
    {
        foreach (['website_pages', 'website_sections', 'website_section_items'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumns('website_pages', [
            'slug', 'title', 'status', 'template', 'seo_title', 'seo_description',
            'seo_image', 'published_at',
        ]));
        $this->assertTrue(Schema::hasColumns('website_sections', [
            'website_page_id', 'key', 'type', 'position', 'is_enabled', 'variant', 'config',
        ]));
        $this->assertTrue(Schema::hasColumns('website_section_items', [
            'website_section_id', 'reference_type', 'reference_id', 'position', 'is_enabled', 'config',
        ]));

        $pageIndexes = collect(Schema::getIndexes('website_pages'))->pluck('name');
        $sectionIndexes = collect(Schema::getIndexes('website_sections'))->pluck('name');
        $itemIndexes = collect(Schema::getIndexes('website_section_items'))->pluck('name');

        $this->assertContains('website_pages_slug_unique', $pageIndexes);
        $this->assertContains('website_sections_page_key_unique', $sectionIndexes);
        $this->assertContains('website_sections_page_position_index', $sectionIndexes);
        $this->assertContains('website_section_items_reference_unique', $itemIndexes);
        $this->assertContains('website_section_items_reference_index', $itemIndexes);
    }

    public function test_models_cast_config_and_return_ordered_relationships(): void
    {
        $page = WebsitePage::create([
            'slug' => 'home',
            'title' => 'Trang chủ',
            'status' => WebsitePage::STATUS_PUBLISHED,
            'published_at' => now()->subMinute(),
        ]);
        $second = $page->sections()->create([
            'key' => 'featured', 'type' => 'product_grid', 'position' => 20,
            'config' => ['limit' => 8],
        ]);
        $first = $page->sections()->create([
            'key' => 'hero', 'type' => 'hero', 'position' => 10,
            'config' => ['variant' => 'full'],
        ]);
        $second->items()->create([
            'reference_type' => 'product', 'reference_id' => 8, 'position' => 20,
            'config' => ['label' => 'B'],
        ]);
        $second->items()->create([
            'reference_type' => 'product', 'reference_id' => 4, 'position' => 10,
            'config' => ['label' => 'A'],
        ]);

        $this->assertSame([$first->id, $second->id], $page->fresh()->sections->pluck('id')->all());
        $this->assertSame([4, 8], $second->fresh()->items->pluck('reference_id')->all());
        $this->assertSame(['limit' => 8], $second->fresh()->config);
        $this->assertTrue($second->fresh()->is_enabled);
        $this->assertSame($page->id, $first->page->id);
        $this->assertSame($second->id, $second->items->first()->section->id);
        $this->assertSame([$page->id], WebsitePage::published()->pluck('id')->all());
    }

    public function test_unique_contracts_reject_duplicate_page_slug_section_key_and_reference(): void
    {
        $page = WebsitePage::create(['slug' => 'home', 'title' => 'Home']);
        $section = $page->sections()->create(['key' => 'featured', 'type' => 'product_grid']);
        $section->items()->create(['reference_type' => 'product', 'reference_id' => 10]);

        foreach ([
            fn () => WebsitePage::create(['slug' => 'home', 'title' => 'Duplicate']),
            fn () => $page->sections()->create(['key' => 'featured', 'type' => 'other']),
            fn () => $section->items()->create(['reference_type' => 'product', 'reference_id' => 10]),
        ] as $duplicate) {
            try {
                $duplicate();
                $this->fail('Unique database contract must reject duplicate data.');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_deleting_page_cascades_sections_and_items(): void
    {
        $page = WebsitePage::create(['slug' => 'home', 'title' => 'Home']);
        $section = $page->sections()->create(['key' => 'hero', 'type' => 'hero']);
        $item = $section->items()->create(['config' => ['title' => 'Slide']]);

        $page->delete();

        $this->assertDatabaseMissing('website_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('website_section_items', ['id' => $item->id]);
    }

    public function test_migration_rolls_back_only_new_content_tables(): void
    {
        Schema::create('legacy_guard', fn ($table) => $table->id());

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('website_section_items'));
        $this->assertFalse(Schema::hasTable('website_sections'));
        $this->assertFalse(Schema::hasTable('website_pages'));
        $this->assertTrue(Schema::hasTable('legacy_guard'));
        Schema::dropIfExists('legacy_guard');
    }

    private function migration(): object
    {
        return require base_path($this->migrationPath);
    }

    private function dropTables(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('website_section_items');
        Schema::dropIfExists('website_sections');
        Schema::dropIfExists('website_pages');
        Schema::enableForeignKeyConstraints();
    }
}
