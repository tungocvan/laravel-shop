<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteReleaseGateTest extends TestCase
{
    public function test_obsolete_website_services_and_misplaced_model_are_removed(): void
    {
        foreach ([
            'Modules/Website/Services/ProductService.php',
            'Modules/Website/Services/CategoryService.php',
            'Modules/Website/Services/ContentService.php',
            'Modules/Website/Services/MarketingService.php',
            'Modules/Admin/Models/AffiliateLevel.php',
        ] as $path) {
            $this->assertFileDoesNotExist(base_path($path));
        }

        $this->assertSame([], glob(base_path('Modules/Website/Services/Services/**/*.php')) ?: []);
    }

    public function test_website_seeders_follow_linux_psr4_case(): void
    {
        foreach (glob(base_path('Modules/Website/database/Seeders/*.php')) as $path) {
            $contents = file_get_contents($path);
            $this->assertStringContainsString('namespace Modules\Website\database\Seeders;', $contents, $path);
            $this->assertStringNotContainsString('namespace Modules\Website\Database\Seeders;', $contents, $path);
        }
    }

    public function test_release_documentation_is_present(): void
    {
        foreach (['ANALYSIS.md', 'INFORMATION.md', 'README.md', 'REFACTOR_PLAN.md', 'PHASE_8_ANALYSIS.md', 'PHASE_8_COMPLETION.md'] as $file) {
            $this->assertFileExists(base_path('docs/modules/Website/'.$file));
        }
    }
}
