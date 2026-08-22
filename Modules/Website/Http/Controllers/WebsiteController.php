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
        $savedAppearance = $settings->get('website.appearance');
        $appearance = $appearanceService->resolve(is_array($savedAppearance) ? $savedAppearance : null);

        return response()->json([
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
                [
                    'src' => '/pwa/icon.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/pwa/icon-maskable.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
