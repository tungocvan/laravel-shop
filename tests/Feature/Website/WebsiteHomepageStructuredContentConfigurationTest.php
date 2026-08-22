<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageStructuredContentConfigurationTest extends TestCase
{
    public function test_homepage_admin_loads_business_content_from_structured_content_service(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));

        $this->assertStringContainsString('loadStructuredContent($homepage)', $component);
        $this->assertStringContainsString("referenceIds('categories', 'category', 'home_category_ids')", $component);
        $this->assertStringContainsString("referenceIds('featured', 'product', 'home_featured_ids')", $component);
        $this->assertStringContainsString("limit('new_arrivals', 'home_new_arrivals_count', 10)", $component);
        $this->assertStringContainsString("config('promo_banner', 'home_promo_banner')", $component);
        $this->assertStringContainsString("itemConfigs('trust_badges', 'home_trust_badges')", $component);
        $this->assertStringNotContainsString('public function loadSettings(', $component);
    }

    public function test_homepage_save_targets_structured_state_before_legacy_compatibility_mirror(): void
    {
        $writer = file_get_contents(base_path('Modules/Website/Services/HomepageContentWriteService.php'));
        $structured = file_get_contents(base_path('Modules/Website/Services/HomepageStructuredContentService.php'));

        $builderPosition = strpos($writer, '$this->builderPersistence->sync(');
        $structuredPosition = strpos($writer, '$this->structuredContent->sync($values)');
        $legacyPosition = strpos($writer, '$this->settings->updateMany($values, \'homepage\')');

        $this->assertNotFalse($builderPosition);
        $this->assertNotFalse($structuredPosition);
        $this->assertNotFalse($legacyPosition);
        $this->assertLessThan($structuredPosition, $builderPosition);
        $this->assertLessThan($legacyPosition, $structuredPosition);

        $this->assertStringContainsString("syncReferences(\$sections->get('categories'), 'category'", $structured);
        $this->assertStringContainsString("syncReferences(\$sections->get('featured'), 'product'", $structured);
        $this->assertStringContainsString("syncItemConfigs(\$sections->get('trust_badges')", $structured);
        $this->assertStringContainsString("mergeConfig(\$sections->get('promo_banner')", $structured);
        $this->assertStringContainsString("mergeConfig(\$sections->get('newsletter')", $structured);
    }

    public function test_legacy_backfill_is_only_used_when_structured_homepage_does_not_exist(): void
    {
        $writer = file_get_contents(base_path('Modules/Website/Services/HomepageContentWriteService.php'));
        $content = file_get_contents(base_path('Modules/Website/Services/HomepageContentService.php'));

        $this->assertStringContainsString("if (! WebsitePage::query()->where('slug', 'home')->exists())", $writer);
        $this->assertStringContainsString('$this->backfill->backfill(true, $sectionOrder)', $writer);
        $this->assertStringContainsString("'source' => 'structured'", $writer);

        $this->assertStringContainsString('if (! $section)', $content);
        $this->assertStringContainsString('$this->settings->get($legacyKey', $content);
    }
}
