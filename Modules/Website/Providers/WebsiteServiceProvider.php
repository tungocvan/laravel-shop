<?php

namespace Modules\Website\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\FooterService;
use Modules\Website\Services\FooterLayoutService;
use Modules\Website\Services\FooterPresentationService;
use Modules\Website\Services\HeaderLayoutService;
use Modules\Website\Services\HeaderMenuService;
use Modules\Website\Services\HeaderPresentationService;
use Modules\Website\Services\StorefrontCacheService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class WebsiteServiceProvider extends ServiceProvider
{
    protected string $name = 'Website';
    protected string $nameLower = 'website';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->registerLivewireComponents();
        $this->registerViewComposers();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void {}

    protected function registerCommandSchedules(): void {}

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$configKey);
                    $normalized = [];

                    foreach ($segments as $segment) {
                        if (end($normalized) === $segment) {
                            continue;
                        }
                        $normalized[] = $segment;
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);
                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->name);

        Blade::componentNamespace('Modules\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('website.footer.footer-info', \Modules\Website\Livewire\Admin\Footer\FooterInfo::class);
        Livewire::component('website.footer.footer-columns', \Modules\Website\Livewire\Admin\Footer\FooterColumns::class);
        Livewire::component('website.footer.social-links', \Modules\Website\Livewire\Admin\Footer\SocialLinks::class);
        Livewire::component('website.footer.footer-settings-hub', \Modules\Website\Livewire\Admin\Footer\FooterSettingsHub::class);
        Livewire::component('website.header.header-settings-hub', \Modules\Website\Livewire\Admin\Header\HeaderSettingsHub::class);
    }

    protected function registerViewComposers(): void
    {
        View::composer(['Website::partials.header'], function ($view) {
            $settings = app(SettingsService::class);
            $savedPresentation = $settings->get('header.presentation');
            $savedLayout = $settings->get('header.layout');

            $view->with([
                'headerLayout' => app(HeaderLayoutService::class)->resolvedLayout(is_array($savedLayout) ? $savedLayout : null),
                'headerPresentation' => app(HeaderPresentationService::class)->resolve(is_array($savedPresentation) ? $savedPresentation : null),
                'headerSettings' => [
                    'brand_name' => $settings->get('header.brand_name', $settings->get('site_name', 'FlexBiz')),
                    'logo' => $settings->get('site_logo'),
                ],
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
                    'brand_name' => $settings->get('footer.brand_name', $settings->get('site_name', 'FlexBiz')),
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
