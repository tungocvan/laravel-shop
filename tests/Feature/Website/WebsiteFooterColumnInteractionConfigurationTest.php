<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterColumnInteractionConfigurationTest extends TestCase
{
    public function test_footer_columns_support_scoped_link_drag_drop_and_column_duplication(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterColumns.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-columns.blade.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/FooterService.php'));

        $this->assertStringContainsString('duplicateColumn(int $id', $component);
        $this->assertStringContainsString('updateLinkOrder(int $columnId, array $orderedIds', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.footer.manage')", $component);

        $this->assertStringContainsString('wire:click="duplicateColumn(', $view);
        $this->assertStringContainsString('Nhân bản', $view);
        $this->assertStringContainsString('Sortable.create($el', $view);
        $this->assertStringContainsString('$wire.updateLinkOrder({{ $column->id }}, this.toArray())', $view);
        $this->assertStringContainsString("handle: '.drag-handle'", $view);

        $this->assertStringContainsString('DB::transaction', $service);
        $this->assertStringContainsString("where('footer_column_id', $columnId)", $service);
        $this->assertStringContainsString("'route_name' => $link->route_name", $service);
        $this->assertStringContainsString("'new_tab' => (bool) $link->new_tab", $service);
    }

    public function test_footer_brand_name_is_independent_from_header_brand_name(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterInfo.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString("get('footer.brand_name'", $component);
        $this->assertStringContainsString("'footer.brand_name'", $component);
        $this->assertStringContainsString('wire:model="brand_name"', $view);
        $this->assertStringContainsString("get('footer.brand_name', $settings->get('site_name', 'FlexBiz'))", $provider);

        $footerComposer = substr($provider, strpos($provider, "View::composer(['Website::partials.footer']"));
        $this->assertStringNotContainsString("get('header.brand_name'", $footerComposer);
    }
}
