<?php

namespace Modules\ClientPortal\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

class PwaSettingsController extends Controller
{
    public function edit(ClientPortalSettingsService $settings): View
    {
        return view('ClientPortal::admin.pwa-settings', [
            'general' => $settings->pwaGeneral(),
            'login' => $settings->pwaLogin(),
        ]);
    }

    public function updateGeneral(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'application_name' => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:30'],
            'browser_title' => ['required', 'string', 'max:150'],
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'apple_title' => ['required', 'string', 'max:30'],
        ]);

        $settings->updatePwaGeneral($validated, $request->user('admin')?->getAuthIdentifier());

        return back()->with('success', 'Đã cập nhật cấu hình PWA chung.');
    }

    public function updateLogin(Request $request, ClientPortalSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:100'],
            'heading' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:1000'],
            'show_intro_panel' => ['required', 'boolean'],
            'back_to_website_text' => ['required', 'string', 'max:60'],
            'web_mode_label' => ['required', 'string', 'max:60'],
            'standalone_mode_label' => ['required', 'string', 'max:60'],
            'feature_cards' => ['required', 'array', 'max:8'],
            'feature_cards.*.enabled' => ['required', 'boolean'],
            'feature_cards.*.title' => ['nullable', 'string', 'max:80'],
            'feature_cards.*.description' => ['nullable', 'string', 'max:240'],
        ]);

        $validated['feature_cards'] = collect($validated['feature_cards'])
            ->map(fn (array $card): array => [
                'enabled' => (bool) $card['enabled'],
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
            ])
            ->values()
            ->all();

        $settings->updatePwaLogin($validated, $request->user('admin')?->getAuthIdentifier());

        return back()->with('success', 'Đã cập nhật nội dung giao diện đăng nhập PWA.');
    }
}
