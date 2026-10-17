<?php

namespace Modules\\Website\\Tests\\Feature;

use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Modules\\Website\\Models\\HeaderMenu;
use Modules\\Website\\Services\\HeaderMenuService;
use Tests\\TestCase;

class PharmaHeaderDefaultNavigationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_initializes_once_and_never_restores_deleted_navigation(): void
    {
        $service = app(HeaderMenuService::class);
        $menu = $service->initializePharmaNavigation();
        $this->assertSame(['Trang chủ', 'Danh mục dược phẩm'], $menu->items()->orderBy('sort_order')->pluck('title')->all());
        $this->assertSame(['/', '/product'], $menu->items()->orderBy('sort_order')->pluck('url')->all());
        $service->initializePharmaNavigation();
        $this->assertSame(2, $menu->items()->count());
        $menu->items()->delete();
        $service->initializePharmaNavigation();
        $this->assertSame(0, $menu->items()->count());
    }

    public function test_preserves_existing_pharma_menu_items(): void
    {
        $menu = HeaderMenu::create(['location' => 'pharma_primary', 'name' => 'Custom', 'is_active' => true]);
        $menu->items()->create(['title' => 'Tùy chỉnh', 'url' => '/custom', 'sort_order' => 1, 'is_active' => true]);
        app(HeaderMenuService::class)->initializePharmaNavigation();
        $this->assertSame(['Tùy chỉnh'], $menu->items()->pluck('title')->all());
    }
}
