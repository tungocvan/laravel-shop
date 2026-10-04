<?php

namespace Modules\ClientPortal\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

class PwaSettingsController extends Controller
{
    public function edit(ClientPortalSettingsService $settings, ApplicationRegistry $registry): View
    {
        return view('ClientPortal::admin.pwa-settings', [
            'general' => $settings->pwaGeneral(),
            'login' => $settings->pwaLogin(),
            'bottomNavigation' => $settings->pwaBottomNavigation(),
            'bottomNavigationThemes' => $settings->pwaBottomNavigationThemes(),
            'adminUi' => config('clientportal.pwa.admin', []),
        ]);
    }

    public function editLauncher(ClientPortalSettingsService $settings, ApplicationRegistry $registry): View
    {
        $applications = $registry->all();
        return view('ClientPortal::admin.launcher-settings', [
            'general' => $settings->pwaGeneral(),
            'launcher' => $settings->pwaLauncher(),
            'applications' => $applications->map(fn (array $application): array => ['manifest' => $application, 'presentation' => $settings->applicationPresentation($application)]),
        ]);
    }

    public function editApplication(string $application, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $manifest = $registry->find($application);
        abort_if($manifest === null, 404);

        return view('ClientPortal::admin.application-presentation', [
            'application' => $manifest,
            'applicationPresentation' => $settings->applicationPresentation($manifest),
            'hubPresentation' => $settings->applicationHubPresentation($manifest),
            'navigationPresentation' => $settings->applicationNavigationPresentation($manifest),
            'features' => collect($manifest['features'] ?? [])->map(fn (array $feature): array => [
                'manifest' => $feature,
                'presentation' => $settings->featurePresentation($manifest['key'], $feature),
            ]),
        ]);
    }

    public function updateGeneral(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'application_name' => ['required', 'string', 'max:100'], 'short_name' => ['required', 'string', 'max:30'],
            'browser_title' => ['required', 'string', 'max:150'], 'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'apple_title' => ['required', 'string', 'max:30'],
        ]);
        $settings->updatePwaGeneral($validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật cấu hình PWA chung.');
    }

    public function updateBottomNavigation(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'presentation_style' => ['required', 'in:default,neumorphism'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_opacity' => ['required', 'integer', 'min:0', 'max:100'],
            'icon_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active_icon_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_font_size' => ['required', 'integer', 'min:9', 'max:18'],
            'icon_size' => ['required', 'integer', 'min:16', 'max:36'],
            'min_height' => ['required', 'integer', 'min:56', 'max:120'],
        ]);
        $settings->updatePwaBottomNavigation($validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật giao diện Bottom Navigation dùng chung.');
    }

    public function resetBottomNavigation(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $settings->resetPwaBottomNavigation($request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã khôi phục Bottom Navigation về giao diện mặc định.');
    }

    public function saveBottomNavigationTheme(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate(['theme_name' => ['required', 'string', 'max:80']]);
        $settings->savePwaBottomNavigationTheme($validated['theme_name'], $settings->pwaBottomNavigation());
        return back()->with('success', 'Đã lưu theme Bottom Navigation "'.$validated['theme_name'].'".');
    }

    public function applyBottomNavigationTheme(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate(['theme_key' => ['required', 'string', 'max:191']]);
        abort_unless($settings->applyPwaBottomNavigationTheme($validated['theme_key'], $request->user('admin')?->getAuthIdentifier()), 404);
        return back()->with('success', 'Đã áp dụng theme Bottom Navigation.');
    }

    public function updateLogin(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:100'], 'heading' => ['required', 'string', 'max:200'], 'description' => ['required', 'string', 'max:1000'],
            'show_intro_panel' => ['required', 'boolean'], 'back_to_website_text' => ['required', 'string', 'max:60'], 'web_mode_label' => ['required', 'string', 'max:60'],
            'standalone_mode_label' => ['required', 'string', 'max:60'], 'feature_cards' => ['required', 'array', 'max:8'],
            'feature_cards.*.enabled' => ['required', 'boolean'], 'feature_cards.*.title' => ['nullable', 'string', 'max:80'], 'feature_cards.*.description' => ['nullable', 'string', 'max:240'],
        ]);
        $validated['feature_cards'] = collect($validated['feature_cards'])->map(fn (array $card): array => [
            'enabled' => (bool) $card['enabled'], 'title' => trim((string) ($card['title'] ?? '')), 'description' => trim((string) ($card['description'] ?? '')),
        ])->values()->all();
        $settings->updatePwaLogin($validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật nội dung giao diện đăng nhập PWA.');
    }

    public function updateLauncher(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'browser_title' => ['required', 'string', 'max:150'], 'brand_title' => ['required', 'string', 'max:80'], 'brand_subtitle' => ['nullable', 'string', 'max:80'],
            'workspace_label' => ['nullable', 'string', 'max:80'], 'heading' => ['required', 'string', 'max:150'], 'description' => ['required', 'string', 'max:1000'],
            'install_button_text' => ['required', 'string', 'max:60'], 'logout_button_text' => ['required', 'string', 'max:60'], 'open_application_text' => ['required', 'string', 'max:60'],
            'empty_title' => ['required', 'string', 'max:120'], 'empty_description' => ['required', 'string', 'max:500'], 'show_source_module' => ['required', 'boolean'],
        ]);
        $settings->updatePwaLauncher($validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật giao diện Application Launcher.');
    }

    public function updateApplication(Request $request, string $application, ApplicationRegistry $registry, ClientPortalSettingsService $settings): RedirectResponse
    {
        $manifest = $registry->find($application); abort_if($manifest === null, 404);
        $validated = $request->validate(['enabled' => ['required', 'boolean'], 'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999']]);
        $settings->updateApplicationPresentation($manifest['key'], $validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật cách hiển thị ứng dụng '.$manifest['name'].'.');
    }

    public function updateApplicationHub(Request $request, string $application, ApplicationRegistry $registry, ClientPortalSettingsService $settings): RedirectResponse
    {
        $manifest = $registry->find($application); abort_if($manifest === null, 404);
        $validated = $request->validate([
            'eyebrow' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'supporting_visible' => ['required', 'boolean'],
            'supporting_title' => ['nullable', 'string', 'max:160'],
            'supporting_body' => ['nullable', 'string', 'max:1000'],
        ]);
        $settings->updateApplicationHubPresentation($manifest['key'], $validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật Hub của ứng dụng '.$manifest['name'].'.');
    }

    public function updateApplicationNavigation(Request $request, string $application, ApplicationRegistry $registry, ClientPortalSettingsService $settings): RedirectResponse
    {
        $manifest = $registry->find($application); abort_if($manifest === null, 404);
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.key' => ['required', 'string'],
            'items.*.bottom_enabled' => ['required', 'boolean'],
            'items.*.bottom_sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.bottom_icon' => ['required', 'string', 'max:60'],
        ]);
        $allowed = collect($manifest['navigation'] ?? [])->pluck('key')->flip();
        $items = collect($validated['items'])->filter(fn (array $item): bool => $allowed->has($item['key']))->map(fn (array $item): array => [
            'key' => $item['key'],
            'bottom_enabled' => (bool) $item['bottom_enabled'],
            'bottom_sort_order' => (int) $item['bottom_sort_order'],
            'bottom_icon' => trim((string) $item['bottom_icon']),
        ])->values()->all();
        $settings->updateApplicationNavigationPresentation($manifest['key'], ['items' => $items], $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật Bottom Navigation của ứng dụng '.$manifest['name'].'.');
    }

    public function updateFeature(Request $request, string $application, string $feature, ApplicationRegistry $registry, ClientPortalSettingsService $settings): RedirectResponse
    {
        $manifest = $registry->find($application); abort_if($manifest === null, 404);
        $featureManifest = collect($manifest['features'] ?? [])->first(fn (array $row): bool => $row['key'] === $feature); abort_if($featureManifest === null, 404);
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'], 'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'], 'badge' => ['nullable', 'string', 'max:30'],
            'eyebrow' => ['required', 'string', 'max:80'], 'page_title' => ['required', 'string', 'max:160'], 'page_description' => ['nullable', 'string', 'max:500'],
            'maintenance' => ['required', 'boolean'], 'maintenance_message' => ['nullable', 'string', 'max:300'],
        ]);
        $settings->updateFeaturePresentation($manifest['key'], $featureManifest['key'], $validated, $request->user('admin')?->getAuthIdentifier());
        return back()->with('success', 'Đã cập nhật presentation cho chức năng '.$featureManifest['name'].'.');
    }
}
