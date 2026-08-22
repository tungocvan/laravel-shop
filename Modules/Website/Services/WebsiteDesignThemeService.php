<?php

namespace Modules\Website\Services;

use InvalidArgumentException;
use Modules\System\Services\SettingsService;

class WebsiteDesignThemeService
{
    public const SETTING_KEY = 'website.design_themes';
    public const SCHEMA = 'flexbiz.website-design-theme';
    public const VERSION = 2;
    public const LEGACY_VERSION = 1;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly WebsiteDesignService $designService,
        private readonly WebsiteLayoutPresentationService $layoutService,
        private readonly WebsiteAppearanceService $appearanceService,
    ) {}

    public function all(): array
    {
        $themes = $this->settings->get(self::SETTING_KEY, []);
        return is_array($themes) ? $themes : [];
    }

    public function defaultThemes(): array
    {
        $designDefaults = $this->designService->resolve();
        $layoutDefaults = $this->layoutService->resolve();
        $appearanceDefaults = $this->appearanceService->resolve();

        $classic = $designDefaults;
        $classic['colors'] = array_replace($classic['colors'], [
            'primary' => '#2563eb', 'secondary' => '#4f46e5', 'background' => '#f8fafc',
            'surface' => '#ffffff', 'text' => '#0f172a', 'muted' => '#64748b', 'border' => '#e2e8f0',
        ]);
        $classic['layout']['default_container'] = 'standard';

        $commerce = $designDefaults;
        $commerce['colors'] = array_replace($commerce['colors'], [
            'primary' => '#059669', 'secondary' => '#0f766e', 'background' => '#f8fafc',
            'surface' => '#ffffff', 'text' => '#111827', 'muted' => '#6b7280', 'border' => '#d1d5db', 'success' => '#059669',
        ]);
        $commerce['layout']['default_container'] = 'wide';

        $premium = $designDefaults;
        $premium['colors'] = array_replace($premium['colors'], [
            'primary' => '#7c3aed', 'secondary' => '#a16207', 'background' => '#fafaf9',
            'surface' => '#ffffff', 'text' => '#1c1917', 'muted' => '#78716c', 'border' => '#e7e5e4', 'warning' => '#a16207',
        ]);
        $premium['typography']['base_font_size'] = '17px';
        $premium['layout']['container_width']['standard'] = '1240px';

        return [
            'demo-classic-blue' => [
                'name' => 'Classic Blue', 'design' => $classic, 'layout' => $layoutDefaults,
                'appearance' => array_replace($appearanceDefaults, ['theme_color' => '#2563eb']),
                'features' => ['chat_position' => 'bottom-right', 'back_to_top_position' => 'right-middle'],
            ],
            'demo-commerce-emerald' => [
                'name' => 'Commerce Emerald', 'design' => $commerce,
                'layout' => array_replace_recursive($layoutDefaults, ['main' => ['container' => 'wide']]),
                'appearance' => array_replace($appearanceDefaults, ['theme_color' => '#059669']),
                'features' => ['chat_position' => 'bottom-right', 'back_to_top_position' => 'right-middle'],
            ],
            'demo-premium-violet' => [
                'name' => 'Premium Violet', 'design' => $premium,
                'layout' => array_replace_recursive($layoutDefaults, ['main' => ['container' => 'standard']]),
                'appearance' => array_replace($appearanceDefaults, ['theme_color' => '#7c3aed']),
                'features' => ['chat_position' => 'right-middle', 'back_to_top_position' => 'bottom-right'],
            ],
        ];
    }

    public function restoreDefaultThemes(): void
    {
        $themes = $this->all();
        foreach ($this->defaultThemes() as $slug => $theme) {
            $themes[$slug] = $this->themePayload($theme['name'], $theme['design'], $theme['layout'], $theme['appearance'], $theme['features']);
        }
        $this->persist($themes);
    }

    public function save(string $name, array $design, array $layout = [], array $appearance = [], array $features = [], ?string $slug = null): string
    {
        $themes = $this->all();
        $slug = $slug ?: $this->uniqueSlug($name, $themes);
        $themes[$slug] = $this->themePayload($name, $design, $layout, $appearance, $features);
        $this->persist($themes);
        return $slug;
    }

    public function update(string $slug, array $design, array $layout = [], array $appearance = [], array $features = []): void
    {
        $themes = $this->all();
        $theme = $themes[$slug] ?? null;
        if (! is_array($theme)) throw new InvalidArgumentException('Website design theme không tồn tại.');
        $themes[$slug] = $this->themePayload((string) ($theme['name'] ?? $slug), $design, $layout, $appearance, $features);
        $this->persist($themes);
    }

    public function rename(string $slug, string $name): void
    {
        $themes = $this->all();
        if (! isset($themes[$slug]) || ! is_array($themes[$slug])) throw new InvalidArgumentException('Website design theme không tồn tại.');
        $themes[$slug]['name'] = $this->name($name);
        $themes[$slug]['updated_at'] = now()->toIso8601String();
        $this->persist($themes);
    }

    public function delete(string $slug): void
    {
        $themes = $this->all(); unset($themes[$slug]); $this->persist($themes);
    }

    public function apply(string $slug): array
    {
        $theme = $this->all()[$slug] ?? null;
        if (! is_array($theme)) throw new InvalidArgumentException('Website design theme không tồn tại.');
        return $this->validateTheme($theme);
    }

    public function export(string $slug): string
    {
        $theme = $this->all()[$slug] ?? null;
        if (! is_array($theme)) throw new InvalidArgumentException('Website design theme không tồn tại.');
        return json_encode($this->validateTheme($theme), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function import(string $json, ?string $overrideName = null): string
    {
        if (trim($json) === '') throw new InvalidArgumentException('Vui lòng dán dữ liệu JSON theme trước khi import.');
        try { $theme = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw new InvalidArgumentException('JSON theme không hợp lệ. Vui lòng kiểm tra lại cú pháp.', 0, $e); }
        if (! is_array($theme) || $theme === []) throw new InvalidArgumentException('JSON theme không có dữ liệu để import.');

        $theme = $this->validateTheme($theme);
        $name = filled($overrideName) ? $this->name($overrideName) : $theme['name'];
        return $this->save(
            $name,
            $theme['design'],
            $theme['layout'] ?? $this->layoutService->resolve(),
            $theme['appearance'] ?? $this->appearanceService->resolve(),
            $theme['features'] ?? $this->featureDefaults(),
        );
    }

    private function validateTheme(array $theme): array
    {
        if (($theme['schema'] ?? null) !== self::SCHEMA) throw new InvalidArgumentException('Schema Website design theme không hợp lệ.');
        $version = (int) ($theme['version'] ?? 0);
        if (! in_array($version, [self::LEGACY_VERSION, self::VERSION], true)) throw new InvalidArgumentException('Version Website design theme không được hỗ trợ.');
        if (! isset($theme['name']) || ! is_string($theme['name']) || trim($theme['name']) === '') throw new InvalidArgumentException('Website design theme thiếu tên theme.');
        if (! is_array($theme['design'] ?? null) || $theme['design'] === []) throw new InvalidArgumentException('Website design theme thiếu design payload.');
        foreach (['typography', 'colors', 'layout'] as $key) {
            if (! isset($theme['design'][$key]) || ! is_array($theme['design'][$key]) || $theme['design'][$key] === []) throw new InvalidArgumentException("Website design theme thiếu design.{$key}.");
        }

        if ($version === self::LEGACY_VERSION) {
            $allowed = ['schema', 'version', 'name', 'design', 'updated_at'];
            if (array_diff(array_keys($theme), $allowed) !== []) throw new InvalidArgumentException('Theme v1 chứa field không được hỗ trợ.');
            return [
                'schema' => self::SCHEMA, 'version' => self::LEGACY_VERSION, 'name' => $this->name($theme['name']),
                'design' => $this->designService->resolve($theme['design']),
                'updated_at' => (string) ($theme['updated_at'] ?? now()->toIso8601String()),
            ];
        }

        $allowed = ['schema', 'version', 'name', 'design', 'layout', 'appearance', 'features', 'updated_at'];
        if (array_diff(array_keys($theme), $allowed) !== []) throw new InvalidArgumentException('Theme chứa field không được hỗ trợ.');
        foreach (['layout', 'appearance', 'features'] as $key) {
            if (! is_array($theme[$key] ?? null) || $theme[$key] === []) throw new InvalidArgumentException("Website design theme thiếu {$key} payload.");
        }

        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'name' => $this->name($theme['name']),
            'design' => $this->designService->resolve($theme['design']),
            'layout' => $this->layoutService->resolve($theme['layout']),
            'appearance' => $this->appearanceService->resolve($theme['appearance']),
            'features' => $this->resolveFeatures($theme['features']),
            'updated_at' => (string) ($theme['updated_at'] ?? now()->toIso8601String()),
        ];
    }

    private function themePayload(string $name, array $design, array $layout, array $appearance, array $features): array
    {
        return [
            'schema' => self::SCHEMA, 'version' => self::VERSION, 'name' => $this->name($name),
            'design' => $this->designService->resolve($design),
            'layout' => $this->layoutService->resolve($layout),
            'appearance' => $this->appearanceService->resolve($appearance),
            'features' => $this->resolveFeatures($features),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function resolveFeatures(array $features): array
    {
        $defaults = $this->featureDefaults();
        return [
            'chat_position' => $this->position($features['chat_position'] ?? null, $defaults['chat_position']),
            'back_to_top_position' => $this->position($features['back_to_top_position'] ?? null, $defaults['back_to_top_position']),
        ];
    }

    private function featureDefaults(): array { return ['chat_position' => 'bottom-right', 'back_to_top_position' => 'right-middle']; }
    private function position(mixed $value, string $default): string { return is_string($value) && in_array($value, ['bottom-left','bottom-right','right-middle'], true) ? $value : $default; }
    private function persist(array $themes): void { $this->settings->updateMany([self::SETTING_KEY => $themes], 'website'); }
    private function name(string $name): string { $name = trim($name); if ($name === '' || mb_strlen($name) > 80) throw new InvalidArgumentException('Tên theme phải từ 1 đến 80 ký tự.'); return $name; }
    private function uniqueSlug(string $name, array $themes): string { $base = str($this->name($name))->slug()->toString() ?: 'website-theme'; $slug = $base; $i = 2; while (array_key_exists($slug, $themes)) $slug = $base.'-'.$i++; return $slug; }
}
