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
        $manager->shouldReceive('config')->andReturn([
            'layout' => ['sticky_header' => true],
            'sidebar' => ['enabled' => true, 'mobile_drawer' => true],
            'header' => ['search' => true, 'notifications' => true, 'user_menu' => true, 'brand' => ['enabled' => true]],
        ]);
        $actions = Mockery::mock(AdminHeaderActionService::class);
        $actions->shouldReceive('context')->andReturn(['notifications' => true, 'items' => []]);

        $context = (new AdminHeaderService($manager, $actions))->context();

        $this->assertSame(['sidebar-toggle', 'brand', 'search'], array_column($context['left'], 'key'));
        $this->assertSame(['actions', 'divider', 'user-menu'], array_column($context['right'], 'key'));
    }

    public function test_header_service_prunes_disabled_regions_without_changing_blade(): void
    {
        $manager = Mockery::mock(AdminLayoutManager::class);
        $manager->shouldReceive('config')->andReturn([
            'layout' => ['sticky_header' => false],
            'sidebar' => ['enabled' => false, 'mobile_drawer' => false],
            'header' => ['search' => false, 'notifications' => false, 'user_menu' => false, 'brand' => ['enabled' => false]],
        ]);
        $actions = Mockery::mock(AdminHeaderActionService::class);
        $actions->shouldReceive('context')->andReturn(['notifications' => false, 'items' => []]);

        $context = (new AdminHeaderService($manager, $actions))->context();

        $this->assertFalse($context['sticky']);
        $this->assertSame([], $context['left']);
        $this->assertSame([], $context['right']);
    }

    public function test_header_service_owns_component_registry(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));

        $this->assertStringContainsString("'sidebar-toggle'", $service);
        $this->assertStringContainsString("'brand'", $service);
        $this->assertStringContainsString("'search'", $service);
        $this->assertStringContainsString("'actions'", $service);
        $this->assertStringContainsString("'divider'", $service);
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
        $this->assertStringContainsString('header_height', $view);
        $this->assertStringContainsString('header_style', $view);
        $this->assertStringContainsString('header_padding_x', $view);
        $this->assertStringContainsString('header_action_gap', $view);
        $this->assertStringContainsString('var(--admin-header-background)', $view);
        $this->assertStringContainsString('var(--admin-header-shadow)', $view);
        $this->assertStringNotContainsString('sm:px-6 lg:px-8', $view);
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
}
