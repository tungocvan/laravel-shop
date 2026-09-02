<?php

namespace Modules\Auth\Services;

use Modules\System\Services\SettingsService;

class LoginPresentationService
{
    public const THEMES = ['classic-card', 'split-brand', 'hero-overlay', 'minimal'];

    public function __construct(private readonly SettingsService $settings) {}

    public function forGuard(string $guard, array $fallback = []): array
    {
        $prefix = $guard === 'admin' ? 'auth_login_admin_' : 'auth_login_client_';
        $theme = (string) $this->settings->get($prefix.'theme', 'classic-card');

        if (! in_array($theme, self::THEMES, true)) {
            $theme = 'classic-card';
        }

        $fallbackLogo = $fallback['logo_url'] ?? asset('storage/img/logo.png');
        $logoPath = $this->settings->get($prefix.'logo');
        $backgroundPath = $this->settings->get($prefix.'background');

        return [
            'theme' => $theme,
            'logo_url' => $logoPath ? $this->assetUrl($logoPath) : $fallbackLogo,
            'background_url' => $this->assetUrl($backgroundPath),
            'title_line_1' => (string) $this->settings->get($prefix.'title_line_1', $fallback['title_line_1'] ?? $this->settings->get('site_name_line_1', '')),
            'title_line_2' => (string) $this->settings->get($prefix.'title_line_2', $fallback['title_line_2'] ?? $this->settings->get('site_name_line_2', 'CÔNG TY TNHH INAFO VIỆT NAM')),
            'description' => (string) $this->settings->get($prefix.'description', $fallback['description'] ?? $this->settings->get('login_description', 'Hệ thống quản trị')),
            'primary_color' => $this->normalizeColor((string) $this->settings->get($prefix.'primary_color', '#0f172a')),
            'overlay_opacity' => $this->normalizeOpacity($this->settings->get($prefix.'overlay_opacity', 55)),
            'show_google' => (bool) $this->settings->get($prefix.'show_google', true),
            'footer' => (string) $this->settings->get($prefix.'footer', ''),
        ];
    }

    private function assetUrl(mixed $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path).'?v='.md5($path);
    }

    private function normalizeColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? strtolower($color) : '#0f172a';
    }

    private function normalizeOpacity(mixed $value): int
    {
        return max(0, min(90, (int) $value));
    }
}
