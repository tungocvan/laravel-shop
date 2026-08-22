<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterColumnInteractionConfigurationTest extends TestCase
{
    public function test_footer_columns_support_native_link_drag_drop_and_column_duplication(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterColumns.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-columns.blade.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/FooterService.php'));

        $this->assertStringContainsString('duplicateColumn(int $id', $component);
        $this->assertStringContainsString('moveLinkByDrag(', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.footer.manage')", $component);

        $this->assertStringContainsString('wire:click="duplicateColumn(', $view);
        $this->assertStringContainsString('Nhân bản', $view);
        $this->assertStringContainsString('dragLink: null', $view);
        $this->assertStringContainsString('draggable="{{ $editingLinkId === $link->id', $view);
        $this->assertStringContainsString('@dragstart="dragLink = { linkId:', $view);
        $this->assertStringContainsString('@drop.stop.prevent="dropLink(', $view);
        $this->assertStringContainsString('$wire.moveLinkByDrag(linkId, fromColumnId, Number(toColumnId), ids)', $view);
        $this->assertStringNotContainsString("group: 'footer-menu-links'", $view);

        $this->assertStringContainsString('DB::transaction', $service);
        $this->assertStringContainsString('moveLinkByDrag(int $linkId, int $fromColumnId, int $toColumnId', $service);
        $this->assertStringContainsString("where('footer_column_id', \$fromColumnId)", $service);
        $this->assertStringContainsString("'footer_column_id' => \$toColumnId", $service);
        $this->assertStringContainsString("'route_name' => \$link->route_name", $service);
        $this->assertStringContainsString("'new_tab' => (bool) \$link->new_tab", $service);
    }

    public function test_footer_brand_and_frontend_visual_contracts_are_self_contained(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterInfo.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $brand = file_get_contents(base_path('Modules/Website/resources/views/components/footer/brand.blade.php'));
        $social = file_get_contents(base_path('Modules/Website/resources/views/components/footer/social-links.blade.php'));
        $columns = file_get_contents(base_path('Modules/Website/resources/views/components/footer/menu-columns.blade.php'));

        $this->assertStringContainsString("get('footer.brand_name'", $component);
        $this->assertStringContainsString("'footer.brand_name'", $component);
        $this->assertStringContainsString('wire:model="brand_name"', $view);
        $this->assertStringContainsString("get('footer.brand_name', \$settings->get('site_name', 'FlexBiz'))", $provider);

        $footerComposer = substr($provider, strpos($provider, "View::composer(['Website::partials.footer']"));
        $this->assertStringNotContainsString("get('header.brand_name'", $footerComposer);
        $this->assertStringNotContainsString('<span style="color: var(--footer-accent);">.</span>', $brand);

        $this->assertStringContainsString("str_contains(\$platform, 'facebook')", $social);
        $this->assertStringContainsString("str_contains(\$platform, 'instagram')", $social);
        $this->assertStringContainsString("str_contains(\$platform, 'youtube')", $social);
        $this->assertStringNotContainsString('<i class="{{ $social->icon_class }}', $social);

        $this->assertStringContainsString('lg:col-span-5 grid gap-8', $columns);
        $this->assertStringContainsString('grid-template-columns: repeat(', $columns);
    }
}
