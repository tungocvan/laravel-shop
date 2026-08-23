<?php

namespace Tests\Feature\Admin;

use Mockery;
use Modules\Admin\Services\AdminHeaderService;
use Modules\Admin\Support\AdminLayoutManager;
use Tests\TestCase;

class AdminHeaderContractTest extends TestCase
{
    public function test_header_service_builds_current_default_component_order(): void
    {
        $manager = Mockery::mock(AdminLayoutManager::class);
        $manager->shouldReceive('config')->once()->andReturn([
            'layout' => ['sticky_header' => true],
            'sidebar' => ['enabled' => true, 'mobile_drawer' => true],
            'header' => [
                'search' => true,
                'notifications' => true,
                'user_menu' => true,
            ],
        ]);

        $context = (new AdminHeaderService($manager))->context();

        $this->assertTrue($context['sticky']);
        $this->assertSame(
            ['sidebar-toggle', 'search'],
            array_column($context['left'], 'key'),
        );
        $this->assertSame(
            ['notifications', 'divider', 'user-menu'],
            array_column($context['right'], 'key'),
        );
    }

    public function test_header_service_prunes_disabled_regions_before_rendering(): void
    {
        $manager = Mockery::mock(AdminLayoutManager::class);
        $manager->shouldReceive('config')->once()->andReturn([
            'layout' => ['sticky_header' => false],
            'sidebar' => ['enabled' => false, 'mobile_drawer' => true],
            'header' => [
                'search' => false,
                'notifications' => false,
                'user_menu' => true,
            ],
        ]);

        $context = (new AdminHeaderService($manager))->context();

        $this->assertFalse($context['sticky']);
        $this->assertSame([], $context['left']);
        $this->assertSame(['user-menu'], array_column($context['right'], 'key'));
    }

    public function test_header_registry_uses_only_server_owned_component_views(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));

        $this->assertStringContainsString("'sidebar-toggle'", $service);
        $this->assertStringContainsString("'search'", $service);
        $this->assertStringContainsString("'notifications'", $service);
        $this->assertStringContainsString("'user-menu'", $service);
        $this->assertStringContainsString('Admin::livewire.partials.header.components.', $service);
        $this->assertStringNotContainsString("data_get(\$header, 'view'", $service);
    }

    public function test_header_blade_is_composition_only_and_keeps_current_shell_classes(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));

        $this->assertStringContainsString("\$headerContext['left']", $view);
        $this->assertStringContainsString("\$headerContext['right']", $view);
        $this->assertStringContainsString("@include(\$component['view'])", $view);
        $this->assertStringNotContainsString('AdminLayoutManager::class', $view);

        $this->assertStringContainsString('z-30 flex h-16', $view);
        $this->assertStringContainsString('border-slate-200 bg-white/90', $view);
        $this->assertStringContainsString('sm:px-6 lg:px-8', $view);
    }

    public function test_extracted_header_components_preserve_existing_livewire_widgets(): void
    {
        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/search.blade.php'));
        $notifications = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/notifications.blade.php'));
        $userMenu = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/user-menu.blade.php'));

        $this->assertStringContainsString("@livewire('admin.partials.header-search')", $search);
        $this->assertStringContainsString("@livewire('admin.partials.header-notifications')", $notifications);
        $this->assertStringContainsString("@livewire('admin.partials.header-user')", $userMenu);
    }
}
