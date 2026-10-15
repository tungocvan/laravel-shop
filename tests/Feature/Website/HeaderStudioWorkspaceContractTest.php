<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class HeaderStudioWorkspaceContractTest extends TestCase
{
    public function test_header_workspace_is_preview_first_and_advanced_tools_are_collapsible(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('Thiết kế Header Website', $view);
        $this->assertStringContainsString('id="header-theme-library"', $view);
        $this->assertStringContainsString('id="header-layout-tools"', $view);
        $this->assertStringContainsString('x-show="showLayoutTools"', $view);
        $this->assertStringContainsString('x-show="showThemeLibrary"', $view);
        $this->assertSame(1, substr_count($view, "partials.builder-preview"));
        $this->assertLessThan(strpos($view, 'id="header-layout-tools"'), strpos($view, "partials.builder-preview"));
        $this->assertStringContainsString('wire:click="saveBuilder"', $view);
        $this->assertStringContainsString('wire:click="saveTopbarSettings"', $view);
    }
    public function test_pharma_header_supports_independent_device_topbar_visibility_and_icon_menu(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/builder-preview.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString("'show_desktop' => (bool) (\$values['show_desktop'] ?? true)", $hub);
        $this->assertStringContainsString("'show_mobile' => (bool) (\$values['show_mobile'] ?? false)", $hub);
        $this->assertStringContainsString('wire:model="topbarSettings.show_mobile"', $editor);
        $this->assertStringContainsString('wire:model="topbarSettings.show_desktop"', $editor);
        $this->assertStringContainsString('id="header-account-settings"', $editor);
        $this->assertStringContainsString('id="header-topbar-settings"', $editor);
        $this->assertStringContainsString('id="header-appearance-settings"', $editor);
        $this->assertStringContainsString("topbarSettings['show_mobile']", $preview);
        $this->assertStringContainsString("topbarConfig['show_mobile']", $frontend);
        $this->assertStringContainsString("topbarConfig['show_desktop']", $frontend);
        $this->assertStringContainsString('aria-label="Mở menu điều hướng"', $frontend);
        $this->assertStringContainsString('aria-controls="pharma-mobile-menu"', $frontend);
    }

    public function test_pharma_mobile_header_uses_overlay_drawer_without_inline_expansion(): void
    {
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('x-teleport="body"', $frontend);
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $frontend);
        $this->assertStringContainsString('x-transition:enter-start="translate-x-full"', $frontend);
        $this->assertStringContainsString('aria-label="Đóng menu"', $frontend);
        $this->assertStringContainsString('@keydown.escape.window="menuOpen = false"', $frontend);
        $this->assertStringContainsString('class="hidden text-xs font-medium sm:block"', $frontend);
        $this->assertStringContainsString('lg:ml-0 lg:flex', $frontend);
        $drawer = substr($frontend, strpos($frontend, '<aside id="pharma-mobile-menu"'));
        $this->assertStringNotContainsString('aria-label="Tìm sản phẩm"', $drawer);
        $this->assertStringContainsString("Route::has('login')", $frontend);
        $this->assertStringContainsString("Route::has('register')", $frontend);
    }

    public function test_mobile_account_partial_is_above_navigation_without_nested_dropdown(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/mobile-account-links.blade.php'));

        $this->assertLessThan(
            strpos($header, 'aria-label="Điều hướng Pharma trên di động"'),
            strpos($header, "partials.pharma.mobile-account-links")
        );
        $this->assertStringContainsString('min-h-0 flex-1 overflow-y-auto', $header);
        $this->assertSame(1, substr_count($header, "partials.pharma.account-dropdown"));
        $this->assertStringContainsString('aria-label="Tài khoản của tôi"', $account);
        $this->assertStringContainsString('aria-label="Liên kết tài khoản"', $account);
        $this->assertStringContainsString('method="POST" action="{{ route(\'logout\') }}"', $header);
        $this->assertStringContainsString('@csrf', $header);
        $this->assertStringContainsString('show_orders', $account);
        $this->assertStringContainsString('show_admin', $account);
        $this->assertStringContainsString('$mobileAccountCanAdmin', $account);
    }

    public function test_mobile_drawer_v2_has_branded_header_account_card_and_pinned_footer(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/mobile-account-links.blade.php'));

        $this->assertStringContainsString('FlexBiz - về trang chủ', $header);
        $this->assertStringContainsString('overflow-hidden bg-white text-slate-900 shadow-2xl', $header);
        $this->assertStringContainsString('min-h-0 flex-1 overflow-y-auto overscroll-contain', $header);
        $this->assertStringContainsString('safe-area-inset-bottom', $header);
        $this->assertStringContainsString('Khám phá', $header);
        $this->assertStringContainsString('aria-label="Liên kết tài khoản"', $account);
        $this->assertStringContainsString('grid grid-cols-2 gap-2', $account);
        $this->assertStringContainsString('Đơn hàng của tôi', $account);
        $this->assertStringContainsString('Thông tin cá nhân', $account);
        $this->assertStringContainsString('Đăng xuất', $header);
    }

    public function test_mobile_drawer_has_direct_function_links_without_chevrons_or_search(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $drawer = substr($header, strpos($header, '<aside id="pharma-mobile-menu"'), strpos($header, '</aside>') - strpos($header, '<aside id="pharma-mobile-menu"'));

        $this->assertStringContainsString('aria-label="Điều hướng Pharma trên di động"', $drawer);
        $this->assertStringContainsString('{{ $itemTitle }}</a>', $drawer);
        $this->assertStringNotContainsString('m9 18 6-6-6-6', $drawer);
        $this->assertStringNotContainsString('mobile.search', $drawer);
        $this->assertStringNotContainsString('role="search"', $drawer);
        $this->assertStringNotContainsString('Tìm sản phẩm...', $drawer);
    }

}
