<?php

namespace Tests\Feature\System;

use Modules\System\Livewire\Settings\SettingForm;
use Tests\TestCase;

class SystemSettingFormTest extends TestCase
{
    public function test_setting_form_uses_fixed_tab_component_contract(): void
    {
        $component = new SettingForm();

        $expected = [
            'theme' => 'admin.theme-switcher',
            'general' => 'system.settings.partials.general',
            'menu' => 'admin.header.menu-manager',
            'images' => 'system.settings.partials.images',
            'seo' => 'system.settings.partials.seo',
            'custom' => 'system.settings.partials.custom',
        ];

        $this->assertSame('theme', $component->activeTab);

        foreach ($expected as $tab => $alias) {
            $component->setTab($tab);
            $this->assertSame($tab, $component->activeTab);
            $this->assertSame($alias, $component->getTabComponent());
        }
    }

    public function test_invalid_tab_falls_back_to_theme_instead_of_arbitrary_component(): void
    {
        $component = new SettingForm();
        $component->setTab('evil.component');

        $this->assertSame('theme', $component->activeTab);
        $this->assertSame('admin.theme-switcher', $component->getTabComponent());
    }

    public function test_setting_form_view_has_no_external_editor_dependencies_and_exposes_tab_semantics(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/setting-form.blade.php'));
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/SettingForm.php'));

        $this->assertStringNotContainsString('code.jquery.com', $view);
        $this->assertStringNotContainsString('summernote', strtolower($view));
        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString('role="tab"', $view);
        $this->assertStringContainsString('aria-selected=', $view);
        $this->assertStringContainsString('role="tabpanel"', $view);
        $this->assertStringContainsString('wire:key="tab-{{ $activeTab }}"', $view);
        $this->assertStringContainsString('admin.theme-switcher', $source);
        $this->assertStringContainsString('admin.header.menu-manager', $source);
        $this->assertStringNotContainsString('Setting::', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }
}
