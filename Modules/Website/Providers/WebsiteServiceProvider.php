<?php

namespace Modules\Website\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Order\Contracts\CheckoutContext;
use Modules\Post\Models\Post;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;
use Modules\Website\Models\Banner;
use Modules\Website\Models\WebsitePage;
use Modules\Website\Models\WebsiteSection;
use Modules\Website\Models\WebsiteSectionItem;
use Modules\Website\Services\FooterLayoutService;
use Modules\Website\Services\FooterPresentationService;
use Modules\Website\Services\FooterService;
use Modules\Website\Services\HeaderLayoutService;
use Modules\Website\Services\HeaderMenuService;
use Modules\Website\Services\HeaderPresentationService;
use Modules\Website\Services\HomepageContentService;
use Modules\Website\Services\WebsiteCheckoutContext;

class WebsiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/design.php', 'website.design');
        $this->mergeConfigFrom(__DIR__.'/../Config/header.php', 'website.header');
        $this->mergeConfigFrom(__DIR__.'/../Config/footer.php', 'website.footer');
        $this->app->bind(CheckoutContext::class, WebsiteCheckoutContext::class);
    }

    public function boot(): void
    {
        $clearHomepage = static function (): void {
            HomepageContentService::clearCache();
            Cache::forget('website.homepage.seo');
        };
        foreach ([WebsitePage::class, WebsiteSection::class, WebsiteSectionItem::class, Banner::class] as $model) {
            $model::saved($clearHomepage);
            $model::deleted($clearHomepage);
        }

        $clearSitemap = static fn (): bool => Cache::forget('website.sitemap.xml');
        foreach ([Product::class, Post::class] as $model) {
            $model::saved($clearSitemap);
            $model::deleted($clearSitemap);
        }

        View::composer(['Website::partials.header', 'Website::layouts.master'], function ($view) {
            $settings = app(SettingsService::class);
            $menuService = app(HeaderMenuService::class);
            $savedLayout = $settings->get('header.layout');
            $savedPresentation = $settings->get('header.presentation');

            $view->with([
                'mainMenu' => $menuService->getMenuTreeByLocation('primary'),
                'mobileMenu' => $menuService->getMenuTreeByLocation('mobile'),
                'accountMenu' => $menuService->getMenuTreeByLocation('account'),
                'headerLayout' => app(HeaderLayoutService::class)->resolvedLayout(is_array($savedLayout) ? $savedLayout : null),
                'headerPresentation' => app(HeaderPresentationService::class)->resolve(is_array($savedPresentation) ? $savedPresentation : null),
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
            $home = Schema::hasTable('website_pages')
                ? Cache::remember('website.homepage.seo', now()->addMinutes(15), fn () => WebsitePage::query()
                    ->select(['id', 'seo_title', 'seo_description', 'seo_image'])
                    ->where('slug', 'home')
                    ->first())
                : null;

            $view->with([
                'siteName' => $settings->get('site_name', 'HOMEPAGE'),
                'siteFavicon' => $settings->get('site_favicon'),
                'headerScript' => $settings->get('header_script', ''),
                'websiteDesign' => config('website.design', []),
                'websiteSeo' => [
                    'title' => $home?->seo_title ?: $settings->get('site_name', 'HOMEPAGE'),
                    'description' => $home?->seo_description ?: '',
                    'image' => $home?->seo_image,
                    'canonical' => $settings->get('seo.canonical_url', url()->current()),
                    'robots' => $settings->get('seo.robots', 'index,follow'),
                ],
                'analyticsCode' => $settings->get('analytics_code', ''),
            ]);
        });

        View::composer(['Website::partials.footer'], function ($view) {
            $footerService = app(FooterService::class);
            $settings = app(SettingsService::class);
            $savedPresentation = $settings->get('footer.presentation');
            $savedLayout = $settings->get('footer.layout');
            $footerBrandLogo = $settings->get('footer.brand_logo');

            $view->with([
                'footerColumns' => $footerService->getColumnsForFrontend(),
                'socialLinks' => $footerService->getSocialLinks(),
                'footerLayout' => app(FooterLayoutService::class)->resolvedLayout(is_array($savedLayout) ? $savedLayout : null),
                'footerPresentation' => app(FooterPresentationService::class)->resolve(is_array($savedPresentation) ? $savedPresentation : null),
                'footerSettings' => [
                    'brand_name' => $settings->get('header.brand_name', $settings->get('site_name', 'FlexBiz')),
                    'logo' => $footerBrandLogo ?: $settings->get('site_logo'),
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
