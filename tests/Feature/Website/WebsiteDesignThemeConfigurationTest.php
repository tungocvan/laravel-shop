<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteDesignThemeConfigurationTest extends TestCase
{
    public function test_website_design_theme_contract_supports_crud_export_import_and_preview_first_apply(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/design-themes.blade.php'));
        $shell = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));

        $this->assertStringContainsString("public const SETTING_KEY = 'website.design_themes'", $service);
        $this->assertStringContainsString("public const SCHEMA = 'flexbiz.website-design-theme'", $service);
        $this->assertStringContainsString('array_diff(array_keys($theme), $allowed)', $service);
        $this->assertStringContainsString("'design' => \$this->designService->resolve", $service);

        foreach (['saveDesignTheme', 'applyDesignTheme', 'updateDesignTheme', 'renameDesignTheme', 'deleteDesignTheme', 'exportDesignTheme', 'importDesignTheme'] as $method) {
            $this->assertStringContainsString("function {$method}", $concern);
        }
        $this->assertStringContainsString("authorizeAdminPermission('website.settings.manage')", $concern);
        $this->assertStringContainsString('ManagesWebsiteDesignThemes', $component);
        $this->assertStringContainsString("'themes'", $component);

        foreach (['saveDesignTheme', 'applyDesignTheme', 'updateDesignTheme', 'renameDesignTheme', 'deleteDesignTheme', 'importDesignTheme'] as $method) {
            $this->assertStringContainsString("\$wire.{$method}()", $view);
        }
        $this->assertStringContainsString('wire:click="exportDesignTheme"', $view);
        foreach (['save', 'apply', 'update', 'rename', 'delete', 'import'] as $action) {
            $this->assertStringContainsString("confirm('{$action}'", $view);
        }
        $this->assertStringContainsString('modalOpen', $view);
        $this->assertStringContainsString('wire:model="themeJson"', $view);
        $this->assertStringContainsString('Preview-first', $view);
        $this->assertStringContainsString("Website::livewire.admin.settings.partials.design-themes", $shell);
    }

    public function test_demo_seeder_uses_canonical_default_theme_source_and_is_registered(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $seeder = file_get_contents(base_path('Modules/Website/database/Seeders/WebsiteDesignThemeSeeder.php'));
        $databaseSeeder = file_get_contents(base_path('Modules/Website/database/Seeders/WebsiteDatabaseSeeder.php'));

        foreach (['demo-classic-blue', 'demo-commerce-emerald', 'demo-premium-violet'] as $slug) {
            $this->assertStringContainsString($slug, $service);
        }

        $this->assertStringContainsString('public function defaultThemes(): array', $service);
        $this->assertStringContainsString('public function restoreDefaultThemes(): void', $service);
        $this->assertStringContainsString('WebsiteDesignThemeService::class', $seeder);
        $this->assertStringContainsString('restoreDefaultThemes()', $seeder);
        $this->assertStringContainsString('WebsiteDesignThemeSeeder::class', $databaseSeeder);
    }
}
