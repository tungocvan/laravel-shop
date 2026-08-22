<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterBuilderConfigurationTest extends TestCase
{
    public function test_footer_admin_page_mounts_builder_on_existing_route_page(): void
    {
        $page = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/footer/index.blade.php'));

        $this->assertStringContainsString('Bố cục Footer', $page);
        $this->assertStringContainsString("@livewire('website.admin.footer.footer-settings-hub')", $page);
        $this->assertStringContainsString("@livewire('website.admin.footer.footer-info')", $page);
        $this->assertStringContainsString("@livewire('website.admin.footer.footer-columns')", $page);
        $this->assertStringContainsString("@livewire('website.admin.footer.social-links')", $page);
        $this->assertStringNotContainsString('Sortable.min.js', $page);
    }

    public function test_builder_exposes_toggle_reorder_slot_move_save_and_reset_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        $this->assertStringContainsString('toggleComponent(', $view);
        $this->assertStringContainsString('moveUp(', $view);
        $this->assertStringContainsString('moveDown(', $view);
        $this->assertStringContainsString('moveComponent(', $view);
        $this->assertStringContainsString('wire:click="saveBuilder"', $view);
        $this->assertStringContainsString('wire:click="resetBuilder"', $view);
        $this->assertStringContainsString('presentation.mode', $view);
        $this->assertStringContainsString('presentation.custom.{{ $field }}', $view);
        $this->assertStringNotContainsString('draggable=', strtolower($view));
    }

    public function test_builder_persists_only_safe_layout_contract_and_footer_presentation(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));

        $this->assertStringContainsString("'footer.layout' => \$layout", $component);
        $this->assertStringContainsString("'footer.presentation' => \$presentation", $component);
        $this->assertStringContainsString("'type' => \$item['type']", $component);
        $this->assertStringContainsString("'enabled' => (bool)", $component);
        $this->assertStringContainsString("'config' =>", $component);
        $this->assertStringContainsString('$registry->resolve(', $component);
        $this->assertStringNotContainsString("'view' =>", $component);
    }

    public function test_all_registered_footer_components_are_exposed_to_builder_layout(): void
    {
        $config = require base_path('Modules/Website/Config/footer.php');
        $components = array_keys($config['components'] ?? []);
        $layout = $config['layout'] ?? [];
        $types = [];

        $walk = function (mixed $value) use (&$walk, &$types): void {
            if (! is_array($value)) {
                return;
            }

            if (is_string($value['type'] ?? null)) {
                $types[] = $value['type'];
            }

            foreach ($value as $child) {
                $walk($child);
            }
        };

        $walk($layout);

        foreach ($components as $component) {
            $this->assertContains($component, $types, "Footer component [{$component}] is not represented by the default builder layout.");
        }
    }
}
