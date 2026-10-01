<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaPwaUiParityContractTest extends TestCase
{
    public function test_order_manifest_actions_are_consistent_between_navigation_and_features(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $navigationActions = $manifest['navigation']['orders']['actions'];
        $featureActions = $manifest['features']['orders']['actions'];

        $this->assertSame('client.pharma.orders.post', $navigationActions['post']['permission'] ?? null);
        $this->assertSame('client.pharma.orders.post', $featureActions['post']['permission'] ?? null);
        $this->assertSame(
            array_keys($navigationActions),
            array_keys($featureActions),
            'Order action presentation must not drift between navigation and feature permission trees.'
        );
    }

    public function test_price_list_capability_leaves_global_navigation_at_the_pharma_hub(): void
    {
        $index = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-lists.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-show.blade.php'));
        $approval = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-approval-show.blade.php'));

        foreach ([$index, $detail, $approval] as $view) {
            $this->assertStringContainsString("@section('hide-application-header', true)", $view);
            $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        }

        $this->assertStringContainsString('aria-label="Quay lại Không gian làm việc Pharma"', $index);
        $this->assertStringContainsString("route('client.pharma.dashboard')", $index);
    }

    public function test_price_list_editor_exposes_the_four_step_responsive_wizard_contract(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));
        $wizard = file_get_contents(base_path('resources/js/clientportal/pharma-price-list-wizard.js'));

        foreach ([1, 2, 3, 4] as $step) {
            $this->assertStringContainsString('data-wizard-panel="'.$step.'"', $view);
            $this->assertStringContainsString('data-step-jump="{{ $step }}"', $view);
        }

        $this->assertStringContainsString('01 · Khởi tạo bảng giá', $view);
        $this->assertStringContainsString('02 · Khách hàng & mục đích', $view);
        $this->assertStringContainsString('03 · Sản phẩm & giá', $view);
        $this->assertStringContainsString('04 · Xem lại & lưu', $view);
        $this->assertStringContainsString('id="wizard-back"', $view);
        $this->assertStringContainsString('id="wizard-next"', $view);
        $this->assertStringContainsString('id="wizard-submit"', $view);
        $this->assertStringContainsString('const initPharmaPriceListWizard = () =>', $wizard);
        $this->assertStringContainsString("raw.split('-').reverse().join('/')", $wizard);
    }

    public function test_price_list_wizard_gates_progress_and_keeps_source_initialization_in_step_one(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));
        $wizard = file_get_contents(base_path('resources/js/clientportal/pharma-price-list-wizard.js'));
        $bundle = file_get_contents(base_path('resources/js/tailwind.js'));

        $this->assertStringContainsString('id="source-price-list" name="source_price_list_id"', $view);
        $this->assertStringContainsString("class=\"{{ \$sourcePriceListId ? 'hidden' : '' }} rounded-3xl", $view);
        $this->assertStringContainsString("class=\"{{ \$sourcePriceListId ? '' : 'hidden' }} rounded-3xl", $view);
        $this->assertStringContainsString("<input type=\"hidden\" name=\"source_price_list_id\" value=\"{{ old('source_price_list_id', \$sourcePriceListId) }}\">", $view);
        $this->assertStringContainsString("? !source?.value", $wizard);
        $this->assertStringNotContainsString("source?.value || document.querySelector('[data-global-price-list-mode]')", $wizard);
        $this->assertStringContainsString('id="client-price-list-customer-search"', $view);
        $this->assertStringContainsString('data-customer-option', $view);
        $this->assertStringContainsString('id="source-product-search" type="search"', $view);
        $this->assertStringContainsString('const stepTwoReady = () =>', $wizard);
        $this->assertStringContainsString("back.classList.toggle('hidden', currentStep === 1)", $wizard);
        $this->assertStringContainsString('if (currentStep === 2 && !stepTwoReady()) {', $wizard);
        $this->assertStringContainsString("globalUserPicker?.classList.remove('hidden')", $wizard);
        $this->assertStringContainsString("globalUserSearch?.focus()", $wizard);
        $this->assertStringContainsString('form.reportValidity()', $wizard);
        $this->assertStringContainsString("button.dataset.state = active ? 'active' : completed ? 'completed' : 'pending'", $wizard);
        $this->assertStringContainsString("import './clientportal/pharma-price-list-wizard';", $bundle);
        $this->assertStringContainsString('disabled:cursor-not-allowed disabled:bg-slate-200', $view);
    }

    public function test_price_list_editor_uses_focused_task_shell_and_canonical_business_selectors(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));

        $this->assertStringContainsString("@section('hide-application-header', true)", $view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        $this->assertStringContainsString('aria-label="Quay lại Bảng giá của tôi"', $view);

        $this->assertStringContainsString('Bảng giá khách hàng', $view);
        $this->assertStringContainsString("route('client.pharma.price-lists.create', ['type'=>'global'])", $view);
        $this->assertStringContainsString('Kích hoạt trực tiếp', $view);
        $this->assertStringContainsString('data-date-display="{{ $dateName }}"', $view);
        $this->assertStringContainsString('type="date" data-date-native="{{ $dateName }}" name="{{ $dateName }}"', $view);
        $this->assertStringContainsString("format('d/m/Y')", $view);
        $this->assertStringNotContainsString('data-price-list-manager-combobox', $view);
        $this->assertStringContainsString('id="price-list-bootstrap" method="GET"', $view);
        $this->assertStringContainsString('<select id="source-price-list" name="source_price_list_id"', $view);
        $this->assertStringNotContainsString('<x-search-select', $view);
        $this->assertStringContainsString('id="client-price-list-customer-search"', $view);
        $this->assertStringContainsString('data-customer-option', $view);
        $this->assertStringContainsString('id="source-product-search" type="search"', $view);

        $this->assertStringContainsString('id="load-source-price-list"', $view);
        $wizard = file_get_contents(base_path('resources/js/clientportal/pharma-price-list-wizard.js'));
        $this->assertStringContainsString("document.getElementById('source-price-list')", $wizard);
        $this->assertStringContainsString('const isoDate = raw =>', $wizard);
        $this->assertStringContainsString("document.querySelectorAll('[data-date-display]')", $wizard);
        $this->assertStringContainsString("document.querySelectorAll('[data-date-native]')", $wizard);
        $this->assertStringContainsString('const syncDateDisplay = native =>', $wizard);
        $this->assertStringContainsString('name="source_price_list_id" value="{{ old(\'source_price_list_id\', $sourcePriceListId) }}"', $view);
    }

    public function test_bid_awards_use_focused_mobile_shell_and_managed_feature_presentation(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $index = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-awards.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-show.blade.php'));

        $feature = $manifest['features']['bid-awards'];
        $this->assertSame('client.pharma.bid-awards', $feature['route']);
        $this->assertArrayHasKey('eyebrow', $feature);
        $this->assertArrayHasKey('page_title', $feature);
        $this->assertArrayHasKey('page_description', $feature);

        foreach ([$index, $detail] as $view) {
            $this->assertStringContainsString("@section('hide-application-header', true)", $view);
            $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
            $this->assertStringContainsString("\$featurePresentation['page_title']", $view);
        }

        $this->assertStringContainsString('aria-label="Quay lại Không gian làm việc Pharma"', $index);
        $this->assertStringContainsString("route('client.pharma.dashboard')", $index);
        $this->assertStringContainsString('aria-label="Quay lại Trúng thầu"', $detail);
        $this->assertStringContainsString("route('client.pharma.bid-awards')", $detail);
    }

    public function test_bid_award_subworkspaces_keep_the_focused_pwa_shell_contract(): void
    {
        $paths = [
            'bid-award-allocation.blade.php',
            'bid-award-hospital-allocation.blade.php',
            'bid-award-hospital-policy.blade.php',
            'bid-award-commercial-policy.blade.php',
            'bid-award-manager-assignment.blade.php',
            'bid-award-manager-assignment-user.blade.php',
        ];

        foreach ($paths as $path) {
            $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/'.$path));
            $this->assertStringContainsString("@section('app-dashboard-route', route('client.pharma.dashboard'))", $view, $path);
            $this->assertStringContainsString("@section('hide-application-header', true)", $view, $path);
            $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view, $path);
            $this->assertStringNotContainsString('Admin::', $view, $path);
            $this->assertStringNotContainsString('wire:', $view, $path);
        }
    }

    public function test_bid_award_closeout_keeps_feature_presentation_admin_managed_without_business_rule_drift(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $settings = file_get_contents(base_path('Modules/ClientPortal/Services/ClientPortalSettingsService.php'));
        $admin = file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/Admin/PwaSettingsController.php'));

        $feature = $manifest['features']['bid-awards'];
        $this->assertSame('client.pharma.bid-awards.view', $feature['permission']);
        $this->assertSame('client.pharma.bid-awards.allocate', $feature['actions']['allocate']['permission']);
        $this->assertSame('client.pharma.bid-awards.commercial-policy', $feature['actions']['commercial-policy']['permission']);

        $this->assertStringContainsString("featurePresentation('pharma', 'bid-awards'", $controller);
        $this->assertStringContainsString('public function featurePresentation', $settings);
        $this->assertStringContainsString('page_title', $settings);
        $this->assertStringContainsString('page_description', $settings);
        $this->assertStringContainsString('maintenance_message', $settings);
        $this->assertStringContainsString('function updateFeature', $admin);
        $this->assertStringContainsString('page_title', $admin);
        $this->assertStringContainsString('page_description', $admin);
        $this->assertStringContainsString('maintenance_message', $admin);
    }

}
