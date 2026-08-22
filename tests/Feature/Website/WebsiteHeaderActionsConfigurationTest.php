<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHeaderActionsConfigurationTest extends TestCase
{
    public function test_header_system_actions_are_admin_managed_and_frontend_has_no_account_menu_fallbacks(): void
    {
        $config=file_get_contents(base_path('Modules/Website/Config/header.php'));
        $component=file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/MenuManager.php'));
        $admin=file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/system-actions.blade.php'));
        $actions=file_get_contents(base_path('Modules/Website/resources/views/components/header/actions.blade.php'));
        $account=file_get_contents(base_path('Modules/Website/resources/views/components/header/account.blade.php'));
        $provider=file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString("'actions' => [",$config);
        $this->assertStringContainsString("get('header.actions')",$component);
        $this->assertStringContainsString("set('header.actions'",$component);
        $this->assertStringContainsString('reorderHeaderActions',$component);
        $this->assertStringContainsString('Hành động Header',$admin);
        $this->assertStringContainsString('wire:model="headerActions.account.guest.login_label"',$admin);
        $this->assertStringContainsString('wire:model="headerActions.account.authenticated.logout_label"',$admin);
        $this->assertStringContainsString("\$actions['order']",$actions);
        $this->assertStringContainsString("'headerActions'=>\$headerActions",$provider);
        $this->assertStringContainsString('$accountMenu',$account);
        $this->assertStringNotContainsString("'/my-apps'",$account);
        $this->assertStringNotContainsString('Ứng dụng của tôi',$account);
        $this->assertStringNotContainsString('Hồ sơ cá nhân',$account);
        $this->assertStringNotContainsString('Đơn hàng của tôi',$account);
    }
}
