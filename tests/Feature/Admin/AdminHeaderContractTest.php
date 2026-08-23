<?php

namespace Tests\Feature\Admin;

use Mockery;
use Modules\Admin\Services\AdminHeaderActionService;
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
                'brand' => ['enabled' => true, 'show_title' => true, 'title' => 'Admin'],
                'responsive' => ['mobile_brand' => 'logo-only', 'hide_title_on_mobile' => true],
            ],
        ]);

        $actionService = Mockery::mock(AdminHeaderActionService::class);
        $actionService->shouldReceive('context')->once()->andReturn([
            'notifications' => true,
            'items' => [],
            'mobile_overflow' => true,
            'overflow_secondary_actions' => true,
        ]);

        $context = (new AdminHeaderService($manager, $actionService))->context();

        $this->assertTrue($context['sticky']);
        $this->assertSame(['sidebar-toggle', 'brand', 'search'], array_column($context['left'], 'key'));
        $this->assertSame(['actions', 'divider', 'user-menu'], array_column($context['right'], 'key'));
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
                'brand' => ['enabled' => false],
            ],
        ]);

        $actionService = Mockery::mock(AdminHeaderActionService::class);
        $actionService->shouldReceive('context')->once()->andReturn([
            'notifications' => false,
            'items' => [],
            'mobile_overflow' => true,
            'overflow_secondary_actions' => true,
        ]);

        $context = (new AdminHeaderService($manager, $actionService))->context();

        $this->assertFalse($context['sticky']);
        $this->assertSame([], $context['left']);
        $this->assertSame(['user-menu'], array_column($context['right'], 'key'));
    }

    public function test_header_registry_uses_only_server_owned_component_views(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));

        $this->assertStringContainsString("'sidebar-toggle'", $service);
        $this->assertStringContainsString("'brand'", $service);
        $this->assertStringContainsString("'search'", $service);
        $this->assertStringContainsString("'actions'", $service);
        $this->assertStringContainsString("'user-menu'", $service);
        $this->assertStringContainsString('Admin::livewire.partials.header.components.', $service);
        $this->assertStringNotContainsString("data_get(\$header, 'view'", $service);
    }

    public function test_header_blade_is_composition_only_and_uses_shell_presentation_contract(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));

        $this->assertStringContainsString("\$headerContext['left']", $view);
        $this->assertStringContainsString("\$headerContext['right']", $view);
        $this->assertStringContainsString("@include(\$component['view'], \$component['data'] ?? [])", $view);
        $this->assertStringNotContainsString('AdminLayoutManager::class', $view);
        $this->assertStringContainsString('AdminShellPresentationService::class', $view);
        $this->assertStringContainsString("header_height", $view);
        $this->assertStringContainsString('var(--admin-surface-raised)', $view);
        $this->assertStringContainsString('var(--admin-border-subtle)', $view);
        $this->assertStringContainsString('sm:px-6 lg:px-8', $view);
        $this->assertStringNotContainsString('z-30 flex h-16', $view);
        $this->assertStringNotContainsString('border-slate-200 bg-white/90', $view);
    }

    public function test_extracted_header_components_preserve_existing_livewire_widgets(): void
    {
        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/search.blade.php'));
        $actions = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/actions.blade.php'));
        $userMenu = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/user-menu.blade.php'));

        $this->assertStringContainsString("@livewire('admin.partials.header-search')", $search);
        $this->assertStringContainsString("@livewire('admin.partials.header-notifications')", $actions);
        $this->assertStringContainsString("@livewire('admin.partials.header-user')", $userMenu);
    }

    public function test_professional_header_widgets_keep_compact_accessible_presentation(): void
    {
        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header-search.blade.php'));
        $notifications = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header-notifications.blade.php'));
        $user = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header-user.blade.php'));

        $this->assertStringContainsString('max-w-lg', $search);
        $this->assertStringContainsString('h-10', $search);
        $this->assertStringContainsString('<kbd', $search);
        $this->assertStringContainsString('aria-label="Tìm kiếm nhanh"', $search);

        $this->assertStringContainsString('aria-label="Xem thông báo"', $notifications);
        $this->assertStringNotContainsString('animate-pulse', $notifications);

        $this->assertStringContainsString('aria-haspopup="menu"', $user);
        $this->assertStringContainsString('w-64', $user);
        $this->assertStringContainsString("route('admin.logout')", $user);
        $this->assertStringContainsString('@csrf', $user);
        $this->assertStringContainsString('x-transition:enter=', $user);
    }
}
