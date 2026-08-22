<?php

namespace Modules\Website\Services;

use InvalidArgumentException;
use Modules\System\Services\SettingsService;

class WebsiteDesignThemeService
{
    public const SETTING_KEY = 'website.design_themes';
    public const SCHEMA = 'flexbiz.website-design-theme';
    public const VERSION = 1;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly WebsiteDesignService $designService,
    ) {}

    public function all(): array
    {
        $themes = $this->settings->get(self::SETTING_KEY, []);
        return is_array($themes) ? $themes : [];
    }

    public function defaultThemes(): array
    {
        $defaults = $this->designService->resolve();

        $classic = $defaults;
        $classic['colors'] = array_replace($classic['colors'], [
            'primary' => '#2563eb', 'secondary' => '#4f46e5', 'background' => '#f8fafc',
            'surface' => '#ffffff', 'text' => '#0f172a', 'muted' => '#64748b', 'border' => '#e2e8f0',
        ]);
        $classic['layout']['default_container'] = 'standard';

        $commerce = $defaults;
        $commerce['colors'] = array_replace($commerce['colors'], [
            'primary' => '#059669', 'secondary' => '#0f766e', 'background' => '#f8fafc',
            'surface' => '#ffffff', 'text' => '#111827', 'muted' => '#6b7280', 'border' => '#d1d5db', 'success' => '#059669',
        ]);
        $commerce['typography']['base_font_size'] = '16px';
        $commerce['layout']['default_container'] = 'wide';
        $commerce['layout']['radius'] = ['sm' => '0.5rem', 'md' => '0.75rem', 'lg' => '1rem', 'xl' => '1.25rem'];

        $premium = $defaults;
        $premium['colors'] = array_replace($premium['colors'], [
            'primary' => '#7c3aed', 'secondary' => '#a16207', 'background' => '#fafaf9',
            'surface' => '#ffffff', 'text' => '#1c1917', 'muted' => '#78716c', 'border' => '#e7e5e4', 'warning' => '#a16207',
        ]);
        $premium['typography']['base_font_size'] = '17px';
        $premium['layout']['default_container'] = 'standard';
        $premium['layout']['container_width']['standard'] = '1240px';
        $premium['layout']['radius'] = ['sm' => '0.25rem', 'md' => '0.5rem', 'lg' => '0.75rem', 'xl' => '1rem'];

        return [
            'demo-classic-blue' => ['name' => 'Classic Blue', 'design' => $classic],
            'demo-commerce-emerald' => ['name' => 'Commerce Emerald', 'design' => $commerce],
            'demo-premium-violet' => ['name' => 'Premium Violet', 'design' => $premium],
        ];
    }

    public function restoreDefaultThemes(): void
    {
        $themes = $this->all();
        foreach ($this->defaultThemes() as $slug => $theme) {
            $themes[$slug] = $this->themePayload($theme['name'], $theme['design']);
        }
        $this->persist($themes);
    }

    public function save(string $name, array $design, ?string $slug = null): string
    {
        $themes = $this->all();
        $slug = $slug ?: $this->uniqueSlug($name, $themes);
        $themes[$slug] = $this->themePayload($name, $design);
        $this->persist($themes);
        return $slug;
    }

    public function update(string $slug, array $design): void
    {
        $themes = $this->all();
        $theme = $themes[$slug] ?? null;
        if (! is_array($theme)) {
            throw new InvalidArgumentException('Website design theme không tồn tại.');
        }
        $themes[$slug] = $this->themePayload((string) ($theme['name'] ?? $slug), $design);
        $this->persist($themes);
    }

    public function rename(string $slug, string $name): void
    {
        $themes = $this->all();
        if (! isset($themes[$slug]) || ! is_array($themes[$slug])) {
            throw new InvalidArgumentException('Website design theme không tồn tại.');
        }
        $themes[$slug]['name'] = $this->name($name);
        $themes[$slug]['updated_at'] = now()->toIso8601String();
        $this->persist($themes);
    }

    public function delete(string $slug): void
    {
        $themes = $this->all();
        unset($themes[$slug]);
        $this->persist($themes);
    }

    public function apply(string $slug): array
    {
        $themes = $this->all();
        $theme = $themes[$slug] ?? null;
        if (! is_array($theme)) {
            throw new InvalidArgumentException('Website design theme không tồn tại.');
        }
        return $this->validateTheme($theme)['design'];
    }

    public function export(string $slug): string
    {
        $themes = $this->all();
        $theme = $themes[$slug] ?? null;
        if (! is_array($theme)) {
            throw new InvalidArgumentException('Website design theme không tồn tại.');
        }
        $payload = $this->validateTheme($theme);
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function import(string $json, ?string $overrideName = null): string
    {
        if (trim($json) === '') {
            throw new InvalidArgumentException('Vui lòng dán dữ liệu JSON theme trước khi import.');
        }

        try {
            $theme = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('JSON theme không hợp lệ. Vui lòng kiểm tra lại cú pháp.', 0, $exception);
        }

        if (! is_array($theme) || $theme === []) {
            throw new InvalidArgumentException('JSON theme không có dữ liệu để import.');
        }

        $theme = $this->validateTheme($theme);
        $name = filled($overrideName) ? $this->name($overrideName) : $theme['name'];
        return $this->save($name, $theme['design']);
    }

    private function validateTheme(array $theme): array
    {
        $allowed = ['schema', 'version', 'name', 'design', 'updated_at'];
        if (array_diff(array_keys($theme), $allowed) !== []) {
            throw new InvalidArgumentException('Theme chứa field không được hỗ trợ.');
        }
        if (($theme['schema'] ?? null) !== self::SCHEMA) {
            throw new InvalidArgumentException('Schema Website design theme không hợp lệ.');
        }
        if ((int) ($theme['version'] ?? 0) !== self::VERSION) {
            throw new InvalidArgumentException('Version Website design theme không được hỗ trợ.');
        }
        if (! isset($theme['name']) || ! is_string($theme['name']) || trim($theme['name']) === '') {
            throw new InvalidArgumentException('Website design theme thiếu tên theme.');
        }
        if (! is_array($theme['design'] ?? null) || $theme['design'] === []) {
            throw new InvalidArgumentException('Website design theme thiếu design payload.');
        }

        $requiredDesignKeys = ['typography', 'colors', 'layout'];
        foreach ($requiredDesignKeys as $key) {
            if (! isset($theme['design'][$key]) || ! is_array($theme['design'][$key]) || $theme['design'][$key] === []) {
                throw new InvalidArgumentException("Website design theme thiếu design.{$key}.");
            }
        }

        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'name' => $this->name($theme['name']),
            'design' => $this->designService->resolve($theme['design']),
            'updated_at' => (string) ($theme['updated_at'] ?? now()->toIso8601String()),
        ];
    }

    private function themePayload(string $name, array $design): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'name' => $this->name($name),
            'design' => $this->designService->resolve($design),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function persist(array $themes): void
    {
        $this->settings->updateMany([self::SETTING_KEY => $themes], 'website');
    }

    private function name(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 80) {
            throw new InvalidArgumentException('Tên theme phải từ 1 đến 80 ký tự.');
        }
        return $name;
    }

    private function uniqueSlug(string $name, array $themes): string
    {
        $base = str($this->name($name))->slug()->toString() ?: 'website-theme';
        $slug = $base;
        $index = 2;
        while (array_key_exists($slug, $themes)) {
            $slug = $base.'-'.$index++;
        }
        return $slug;
    }
}
