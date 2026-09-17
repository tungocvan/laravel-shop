<?php

namespace Tests\Feature\System;

use Modules\System\Services\SystemConfigService;
use ReflectionMethod;
use Tests\TestCase;

class SystemTabsRuntimeContractTest extends TestCase
{
    public function test_orphan_override_cannot_create_runtime_tab(): void
    {
        $service = app(SystemConfigService::class);
        $merge = new ReflectionMethod($service, 'mergeById');
        $merge->setAccessible(true);

        $tabs = $merge->invoke($service, [
            [
                'id' => 'queues',
                'label' => 'Queue Manager',
                'component' => 'system.settings.queue-manager',
                'enabled' => true,
            ],
        ], [
            [
                'id' => 'themes',
                'label' => 'Giao diện Sidebar',
                'enabled' => true,
            ],
        ]);

        $this->assertCount(1, $tabs);
        $this->assertSame('queues', $tabs[0]['id']);
        $this->assertArrayHasKey('component', $tabs[0]);
    }

    public function test_known_override_preserves_core_component_contract(): void
    {
        $service = app(SystemConfigService::class);
        $merge = new ReflectionMethod($service, 'mergeById');
        $merge->setAccessible(true);

        $tabs = $merge->invoke($service, [
            [
                'id' => 'mail',
                'label' => 'Email',
                'component' => 'system.settings.mail-config',
                'enabled' => true,
            ],
        ], [
            [
                'id' => 'mail',
                'label' => 'Email hệ thống',
                'enabled' => false,
            ],
        ]);

        $this->assertCount(1, $tabs);
        $this->assertSame('Email hệ thống', $tabs[0]['label']);
        $this->assertSame('system.settings.mail-config', $tabs[0]['component']);
        $this->assertFalse($tabs[0]['enabled']);
    }

    public function test_repository_override_does_not_reintroduce_removed_sidebar_theme_tab(): void
    {
        $tabs = app(SystemConfigService::class)->getTabs();

        $this->assertNotContains('themes', array_column($tabs, 'id'));

        foreach ($tabs as $tab) {
            $this->assertIsString($tab['component'] ?? null);
            $this->assertNotSame('', $tab['component']);
        }
    }

    public function test_controller_guards_component_lookup_for_invalid_tab_data(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/SystemController.php'));

        $this->assertIsString($controller);
        $this->assertStringContainsString("\$component = \$tab['component'] ?? null;", $controller);
        $this->assertStringContainsString('is_string($component)', $controller);
        $this->assertStringNotContainsString("getClass(\$tab['component'])", $controller);
    }
}
