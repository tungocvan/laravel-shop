<?php

namespace Modules\Website\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Website\Services\FooterService;
use Modules\Website\Services\HeaderMenuService;
use Modules\Website\Services\SettingsService;

class WebsiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ... các config khác ...

        // 1. Inject dữ liệu cho HEADER
        View::composer(['Website::partials.header', 'Website::layouts.master'], function ($view) {
            $settings = app(SettingsService::class);
            $menuService = app(HeaderMenuService::class);

            $view->with([
                // Lấy menu Desktop và Mobile
                'mainMenu' => $menuService->getMenuTreeByLocation('primary'),
                'mobileMenu' => $menuService->getMenuTreeByLocation('mobile'),

                // Lấy settings chung (Logo, Hotline...)
                'headerSettings' => [
                    'logo' => $settings->get('site_logo'),
                    'hotline' => $settings->get('header.topbar.hotline', '0903 971 949'),
                    'email' => $settings->get('header.topbar.email', 'contact@flexbiz.com'),
                    'brand_name' => $settings->get('header.brand_name', 'FlexBiz'),
                    'help_url' => $settings->get('header.topbar.help_url', '#'),
                    'order_tracking_url' => $settings->get('header.topbar.order_tracking_url', '#'),
                ],
            ]);
        });

        View::composer(['Website::layouts.frontend', 'Website::pages.home.index', 'Website::pages.help.index'], function ($view) {
            $settings = app(SettingsService::class);

            $view->with([
                'siteName' => $settings->get('site_name', 'HOMEPAGE'),
                'siteFavicon' => $settings->get('site_favicon'),
                'headerScript' => $settings->get('header_script', ''),
            ]);
        });

        // 2. Inject dữ liệu cho FOOTER
        View::composer(['Website::partials.footer'], function ($view) {
            $footerService = app(FooterService::class);
            $settings = app(SettingsService::class);

            $view->with([
                // Lấy cột footer active
                'footerColumns' => $footerService->getColumnsForFrontend(),

                // Lấy social links active
                'socialLinks' => $footerService->getSocialLinks(),

                // Lấy settings footer
                'footerSettings' => [
                    'description' => $settings->get('footer.brand_description'),
                    'address' => $settings->get('footer.address'),
                    'email' => $settings->get('footer.email'),
                    'phone' => $settings->get('footer.phone'),
                    'copyright' => $settings->get('footer.copyright'),
                    'appstore' => $settings->get('footer.appstore_url'),
                    'playstore' => $settings->get('footer.playstore_url'),
                ],
            ]);
        });
    }
}
