<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\WebsiteAppearanceService;

class WebsiteController extends Controller
{
    public function __construct()
    {
        // Reserved for Website-specific middleware.
    }

    public function home()
    {
        return view('Website::pages.home.index');
    }

    public function help()
    {
        return view('Website::pages.help.index');
    }

    public function manifest(SettingsService $settings, WebsiteAppearanceService $appearanceService)
    {
        $appearance = $this->resolvedAppearance($settings, $appearanceService);

        return response()->json([
            'id' => '/',
            'name' => $appearance['application_name'],
            'short_name' => $appearance['apple_title'],
            'description' => $appearance['application_name'],
            'lang' => str_replace('_', '-', app()->getLocale()),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => $appearance['background_color'],
            'theme_color' => $appearance['theme_color'],
            'icons' => [
                ['src' => '/pwa/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
                ['src' => '/pwa/icon-maskable.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function pwaVersion(SettingsService $settings, WebsiteAppearanceService $appearanceService)
    {
        $appearance = $this->resolvedAppearance($settings, $appearanceService);
        $versionPayload = [
            'application_name' => $appearance['application_name'],
            'apple_title' => $appearance['apple_title'],
            'theme_color' => $appearance['theme_color'],
            'background_color' => $appearance['background_color'],
            'manifest_enabled' => $appearance['manifest_enabled'],
            'service_worker_enabled' => $appearance['service_worker_enabled'],
        ];

        return response()->json([
            'version' => substr(hash('sha256', json_encode($versionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 16),
            'manifest' => route('website.manifest'),
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function resolvedAppearance(SettingsService $settings, WebsiteAppearanceService $appearanceService): array
    {
        $savedAppearance = $settings->get('website.appearance');
        $siteName = (string) $settings->get('site_name', 'FlexBiz');

        return $appearanceService->resolve(is_array($savedAppearance) ? $savedAppearance : null, $siteName);
    }

    public function create() { }
    public function store(Request $request) { }
    public function show(string $id) { }
    public function edit(string $id) { }
    public function update(Request $request, string $id) { }
    public function destroy(string $id) { }
}
