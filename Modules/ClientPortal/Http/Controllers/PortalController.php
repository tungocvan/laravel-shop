<?php

namespace Modules\ClientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\ClientPortal\Services\PortalContextResolver;

class PortalController extends Controller
{
    public function manifest(ClientPortalSettingsService $settings): JsonResponse
    {
        $general = $settings->pwaGeneral();

        return response()->json([
            'name' => $general['application_name'],
            'short_name' => $general['short_name'],
            'start_url' => $general['start_url'] ?? '/my-apps',
            'scope' => '/my-apps',
            'display' => $general['display'] ?? 'standalone',
            'theme_color' => $general['theme_color'],
            'background_color' => $general['background_color'],
            'icons' => [
                [
                    'src' => '/pwa/icon.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function login(Request $request, ClientPortalSettingsService $settings): View|RedirectResponse
    {
        if ($request->user('web')) {
            return redirect()->route('client.apps.index');
        }

        return view('ClientPortal::pages.login', [
            'pwaGeneral' => $settings->pwaGeneral(),
            'pwaLogin' => $settings->pwaLogin(),
        ]);
    }

    public function register(Request $request, ClientPortalSettingsService $settings): View|RedirectResponse
    {
        if ($request->user('web')) {
            return redirect()->route('client.apps.index');
        }

        return view('ClientPortal::pages.register', [
            'pwaGeneral' => $settings->pwaGeneral(),
            'pwaLogin' => $settings->pwaLogin(),
        ]);
    }

    public function verifyEmail(Request $request, ClientPortalSettingsService $settings): View|RedirectResponse
    {
        if ($request->user('web')) {
            return redirect()->route('client.apps.index');
        }

        return view('ClientPortal::pages.verify-email', [
            'pwaGeneral' => $settings->pwaGeneral(),
            'pwaLogin' => $settings->pwaLogin(),
        ]);
    }

    public function index(
        Request $request,
        PortalContextResolver $portalContext,
        ClientPortalSettingsService $settings
    ): View|RedirectResponse {
        $context = $portalContext->resolve($request->user('web'));

        if ($context['application_count'] === 1 && is_array($context['single_application'])) {
            return redirect()->route($context['single_application']['route']);
        }

        return view('ClientPortal::pages.apps', [
            'applications' => $settings->presentApplications($context['applications']),
            'portalContext' => $context,
            'pwaGeneral' => $settings->pwaGeneral(),
            'launcher' => $settings->pwaLauncher(),
        ]);
    }
}
