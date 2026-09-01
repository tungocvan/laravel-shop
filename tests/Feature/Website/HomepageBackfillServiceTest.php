<?php

namespace Tests\Feature\Website;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Website\Models\WebsitePage;
use Modules\Website\Services\HomepageBackfillService;
use Modules\Website\Services\HomepageContentService;
use Modules\Website\Services\HomepageContentWriteService;
use Modules\Website\Services\HomepageSectionManagerService;
use Tests\TestCase;

class HomepageBackfillServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTables();
        (require base_path('Modules/Website/database/migrations/2026_08_11_150000_create_website_content_structure.php'))->up();
        $this->createSources();
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function test_dry_run_reports_orphans_without_writing_structured_tables(): void
    {
        $this->seedSettings();

        $report = app(HomepageBackfillService::class)->backfill();

        $this->assertFalse($report['apply']);
        $this->assertSame(10, $report['sections']);
        $this->assertSame([99], $report['missing_category_ids']);
        $this->assertSame([88], $report['missing_product_ids']);
        $this->assertSame(0, WebsitePage::query()->count());
    }

    public function test_apply_is_idempotent_and_preserves_canonical_settings(): void
    {
        $this->seedSettings();
        $service = app(HomepageBackfillService::class);
        $settingsCount = DB::table('settings')->count();

        $service->backfill(true);
        $service->backfill(true);

        $page = WebsitePage::query()->with('sections.items')->where('slug', 'home')->firstOrFail();
        $this->assertSame(10, $page->sections->count());
        $this->assertSame([1], $page->sections->firstWhere('key', 'categories')->items->pluck('reference_id')->all());
        $this->assertSame([2], $page->sections->firstWhere('key', 'featured')->items->pluck('reference_id')->all());
        $this->assertSame(2, $page->sections->firstWhere('key', 'trust_badges')->items->count());
        $this->assertFalse($page->sections->firstWhere('key', 'flash_sale')->is_enabled);
        $this->assertSame(12, $page->sections->firstWhere('key', 'new_arrivals')->config['limit']);
        $this->assertSame($settingsCount, DB::table('settings')->count());
        $this->assertSame(1, WebsitePage::query()->count());
    }

    public function test_structured_reads_match_settings_values_and_fallback_before_backfill(): void
    {
        $this->seedSettings();
        $content = app(HomepageContentService::class);

        $this->assertSame([2, 88], $content->referenceIds('featured', 'product', 'home_featured_ids'));
        $this->assertSame(12, $content->limit('new_arrivals', 'home_new_arrivals_count', 10));

        app(HomepageBackfillService::class)->backfill(true);

        $this->assertSame([2], $content->referenceIds('featured', 'product', 'home_featured_ids'));
        $this->assertSame(12, $content->limit('new_arrivals', 'home_new_arrivals_count', 10));
        $this->assertSame('hidden', $content->visibility()['show_flash_sale']);
        $this->assertSame(['title' => 'Promo'], $content->config('promo_banner', 'home_promo_banner'));
        $this->assertSame([['title' => 'A'], ['title' => 'B']], $content->itemConfigs('trust_badges', 'home_trust_badges'));
    }

    public function test_repeated_backfill_can_enable_a_section_that_was_previously_hidden(): void
    {
        $this->seedSettings();
        $backfill = app(HomepageBackfillService::class);
        $content = app(HomepageContentService::class);
        $backfill->backfill(true);
        $this->assertSame('hidden', $content->visibility()['show_flash_sale']);

        DB::table('settings')->where('key', 'home_show_flash_sale')->update(['value' => 'all']);
        $backfill->backfill(true);

        $this->assertSame('all', $content->visibility()['show_flash_sale']);
        $this->assertTrue(
            WebsitePage::where('slug', 'home')->firstOrFail()->sections()->where('key', 'flash_sale')->value('is_enabled')
        );
    }

    public function test_admin_write_updates_canonical_and_structured_data_in_one_workflow(): void
    {
        $this->seedSettings();
        app(HomepageBackfillService::class)->backfill(true);

        app(HomepageContentWriteService::class)->save([
            'home_show_flash_sale' => 'all',
            'home_new_arrivals_count' => 16,
            'home_promo_banner' => ['title' => 'Updated promo'],
        ], [
            'show_newsletter', 'show_blog_highlight', 'show_trust_badges',
            'show_best_sellers', 'show_new_arrivals', 'show_featured',
            'show_promo_banner', 'show_flash_sale', 'show_categories', 'show_hero',
        ]);

        $content = app(HomepageContentService::class);
        $this->assertSame('all', $content->visibility()['show_flash_sale']);
        $this->assertSame(16, $content->limit('new_arrivals', 'home_new_arrivals_count', 10));
        $this->assertSame(['title' => 'Updated promo'], $content->config('promo_banner', 'home_promo_banner'));
        $this->assertDatabaseHas('settings', ['key' => 'home_show_flash_sale', 'value' => 'all']);
        $this->assertSame('newsletter', $content->order()[0]);
        $this->assertSame('hero', $content->order()[9]);
    }

    public function test_duplicated_section_survives_compatibility_write_and_can_be_deleted(): void
    {
        $this->seedSettings();
        app(HomepageBackfillService::class)->backfill(true);
        $manager = app(HomepageSectionManagerService::class);
        $copy = $manager->duplicate('featured');

        app(HomepageContentWriteService::class)->save([
            'home_show_featured_copy_1' => 'all',
        ], ['show_featured', 'show_featured_copy_1']);

        $this->assertSame('featured_copy_1', $copy->key);
        $this->assertDatabaseHas('website_sections', ['key' => 'featured_copy_1', 'type' => 'product_grid']);
        $this->assertContains('featured_copy_1', app(HomepageContentService::class)->order());

        $manager->remove('featured_copy_1');
        $this->assertDatabaseMissing('website_sections', ['key' => 'featured_copy_1']);
    }

    private function seedSettings(): void
    {
        $settings = [
            ['home_category_ids', [1, 99]],
            ['home_featured_ids', [2, 88]],
            ['home_trust_badges', [['title' => 'A'], ['title' => 'B']]],
            ['home_new_arrivals_count', '12'],
            ['home_show_flash_sale', 'hidden'],
            ['home_promo_banner', ['title' => 'Promo']],
        ];

        foreach ($settings as [$key, $value]) {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => is_array($value) ? 'json' : 'text',
                'group_name' => 'homepage',
            ]);
        }
    }

    private function createSources(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group_name')->default('general');
            $table->string('type')->default('text');
            $table->string('label')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('wp_products', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
        DB::table('categories')->insert(['id' => 1, 'name' => 'Category']);
        DB::table('wp_products')->insert(['id' => 2, 'title' => 'Product']);
    }

    private function dropTables(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['website_section_items', 'website_sections', 'website_pages', 'settings', 'wp_settings', 'categories', 'wp_products'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
}
