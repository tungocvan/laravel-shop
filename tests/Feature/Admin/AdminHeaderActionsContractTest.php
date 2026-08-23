<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHeaderActionsContractTest extends TestCase
{
    public function test_header_composes_actions_as_one_server_owned_pipeline(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));

        $this->assertStringContainsString('AdminHeaderActionService $headerActionService', $service);
        $this->assertStringContainsString("'actions'", $service);
        $this->assertStringContainsString("'Admin::livewire.partials.header.components.actions'", $service);
        $this->assertStringNotContainsString("'Admin::livewire.partials.header.components.notifications'", $service);
    }

    public function test_action_service_normalizes_enabled_permission_order_icon_url_priority_and_badge(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderActionService.php'));

        $this->assertStringContainsString('private const ICONS = [', $service);
        $this->assertStringContainsString("data_get(\$item, 'enabled', true)", $service);
        $this->assertStringContainsString("data_get(\$item, 'permission', '')", $service);
        $this->assertStringContainsString('Gate::forUser($user)->allows($permission)', $service);
        $this->assertStringContainsString("data_get(\$item, 'order', 0)", $service);
        $this->assertStringContainsString('private function safeUrl(', $service);
        $this->assertStringContainsString('private function safeIcon(', $service);
        $this->assertStringContainsString("['http', 'https']", $service);
        $this->assertStringContainsString("'priority'", $service);
        $this->assertStringContainsString("'badge'", $service);
    }

    public function test_notifications_remain_system_owned_inside_actions_view(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/actions.blade.php'));

        $this->assertStringContainsString("@livewire('admin.partials.header-notifications')", $view);
        $this->assertStringContainsString('data-admin-system-action="notifications"', $view);
        $this->assertStringContainsString('data-admin-header-action-priority', $view);
        $this->assertStringContainsString('rel="noopener noreferrer"', $view);
    }

    public function test_configured_actions_cannot_select_runtime_blade_views(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderActionService.php'));

        $this->assertStringNotContainsString("data_get(\$item, 'view'", $service);
        $this->assertStringNotContainsString("data_get(\$item, 'html'", $service);
        $this->assertStringNotContainsString("data_get(\$item, 'svg'", $service);
    }
}
