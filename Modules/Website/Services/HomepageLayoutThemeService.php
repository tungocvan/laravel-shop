<?php

namespace Modules\Website\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\System\Services\SettingsService;

class HomepageLayoutThemeService
{
    public const VERSION = 1;
    public const MAX_THEMES = 20;
    public const SETTING_KEY = 'homepage.layout_themes';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly HomepageSectionRegistry $registry,
        private readonly HomepagePresentationService $presentation,
    ) {}

    public function all(): array
    {
        $themes = $this->settings->get(self::SETTING_KEY, []);

        return is_array($themes) ? $themes : [];
    }

    public function saveNew(string $name, array $sectionOrder, array $layout, array $sectionTypes, array $presentation): array
    {
        $themes = $this->all();
        if (count($themes) >= self::MAX_THEMES) {
            throw new InvalidArgumentException('Chỉ được lưu tối đa '.self::MAX_THEMES.' Homepage themes.');
        }

        $name = $this->validName($name);
        $base = Str::slug($name) ?: 'homepage-theme';
        $slug = $base;
        $suffix = 2;
        while (isset($themes[$slug])) {
            $slug = $base.'-'.$suffix++;
        }

        $themes[$slug] = $this->snapshot($name, $sectionOrder, $layout, $sectionTypes, $presentation);
        $this->persist($themes);

        return [$slug, $themes];
    }

    public function update(string $slug, string $name, array $sectionOrder, array $layout, array $sectionTypes, array $presentation): array
    {
        $themes = $this->all();
        $this->requireTheme($themes, $slug);
        $themes[$slug] = $this->snapshot($this->validName($name), $sectionOrder, $layout, $sectionTypes, $presentation);
        $this->persist($themes);

        return $themes;
    }

    public function rename(string $slug, string $name): array
    {
        $themes = $this->all();
        $this->requireTheme($themes, $slug);
        $themes[$slug]['name'] = $this->validName($name);
        $themes[$slug]['updated_at'] = now()->toIso8601String();
        $this->persist($themes);

        return $themes;
    }

    public function delete(string $slug): array
    {
        $themes = $this->all();
        $this->requireTheme($themes, $slug);
        unset($themes[$slug]);
        $this->persist($themes);

        return $themes;
    }

    public function snapshot(string $name, array $sectionOrder, array $layout, array $sectionTypes, array $presentation): array
    {
        [$safeOrder, $safeVisibility, $safeTypes] = $this->safeLayout($sectionOrder, $layout, $sectionTypes);

        return [
            'version' => self::VERSION,
            'name' => $this->validName($name),
            'layout' => [
                'section_order' => $safeOrder,
                'visibility' => $safeVisibility,
                'section_types' => $safeTypes,
            ],
            'presentation' => $this->presentation->resolve($presentation),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function apply(array $theme): array
    {
        $theme = $this->validateTheme($theme);
        $layout = $theme['layout'];

        return [
            'section_order' => $layout['section_order'],
            'visibility' => $layout['visibility'],
            'section_types' => $layout['section_types'],
            'presentation' => $theme['presentation'],
            'name' => $theme['name'],
        ];
    }

    public function export(array $theme): string
    {
        $theme = $this->validateTheme($theme);

        return json_encode([
            'schema' => 'flexbiz.homepage-layout-theme',
            'version' => self::VERSION,
            'theme' => $theme,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function import(string $json): array
    {
        if (trim($json) === '') {
            throw new InvalidArgumentException('JSON import đang trống.');
        }

        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('JSON import không hợp lệ.', previous: $exception);
        }

        if (! is_array($payload)
            || ($payload['schema'] ?? null) !== 'flexbiz.homepage-layout-theme'
            || (int) ($payload['version'] ?? 0) !== self::VERSION
            || ! is_array($payload['theme'] ?? null)) {
            throw new InvalidArgumentException('File theme không đúng schema/version Homepage.');
        }

        $theme = $this->validateTheme($payload['theme']);
        $themes = $this->all();
        if (count($themes) >= self::MAX_THEMES) {
            throw new InvalidArgumentException('Chỉ được lưu tối đa '.self::MAX_THEMES.' Homepage themes.');
        }

        $base = Str::slug($theme['name']) ?: 'imported-homepage-theme';
        $slug = $base;
        $suffix = 2;
        while (isset($themes[$slug])) {
            $slug = $base.'-'.$suffix++;
        }

        $theme['updated_at'] = now()->toIso8601String();
        $themes[$slug] = $theme;
        $this->persist($themes);

        return [$slug, $themes];
    }

    public function validateTheme(array $theme): array
    {
        $allowedThemeKeys = ['version', 'name', 'layout', 'presentation', 'updated_at'];
        if (array_diff(array_keys($theme), $allowedThemeKeys) !== []) {
            throw new InvalidArgumentException('Theme chứa dữ liệu không thuộc layout/presentation.');
        }

        if ((int) ($theme['version'] ?? 0) !== self::VERSION || ! is_array($theme['layout'] ?? null)) {
            throw new InvalidArgumentException('Homepage theme version/layout không hợp lệ.');
        }

        $layout = $theme['layout'];
        $allowedLayoutKeys = ['section_order', 'visibility', 'section_types'];
        if (array_diff(array_keys($layout), $allowedLayoutKeys) !== []) {
            throw new InvalidArgumentException('Theme layout chứa dữ liệu không được phép.');
        }

        [$order, $visibility, $types] = $this->safeLayout(
            (array) ($layout['section_order'] ?? []),
            (array) ($layout['visibility'] ?? []),
            (array) ($layout['section_types'] ?? [])
        );

        return [
            'version' => self::VERSION,
            'name' => $this->validName((string) ($theme['name'] ?? '')),
            'layout' => [
                'section_order' => $order,
                'visibility' => $visibility,
                'section_types' => $types,
            ],
            'presentation' => $this->presentation->resolve(is_array($theme['presentation'] ?? null) ? $theme['presentation'] : []),
            'updated_at' => (string) ($theme['updated_at'] ?? now()->toIso8601String()),
        ];
    }

    private function safeLayout(array $sectionOrder, array $layout, array $sectionTypes): array
    {
        $safeOrder = [];
        $safeVisibility = [];
        $safeTypes = [];

        foreach ($sectionOrder as $rawLayoutKey) {
            $layoutKey = str_starts_with((string) $rawLayoutKey, 'show_') ? (string) $rawLayoutKey : 'show_'.(string) $rawLayoutKey;
            $sectionKey = substr($layoutKey, 5);

            try {
                $definition = $this->registry->resolve($sectionKey, $sectionTypes[$sectionKey] ?? null);
            } catch (InvalidArgumentException) {
                continue;
            }

            if (in_array($layoutKey, $safeOrder, true)) {
                continue;
            }

            $visibility = (string) ($layout[$layoutKey] ?? 'all');
            if (! in_array($visibility, ['all', 'desktop', 'mobile', 'none', 'hidden'], true)) {
                $visibility = 'all';
            }

            $safeOrder[] = $layoutKey;
            $safeVisibility[$layoutKey] = $visibility === 'hidden' ? 'none' : $visibility;
            $safeTypes[$sectionKey] = (string) $definition['type'];
        }

        if ($safeOrder === []) {
            throw new InvalidArgumentException('Homepage theme không có section hợp lệ.');
        }

        return [$safeOrder, $safeVisibility, $safeTypes];
    }

    private function validName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 80) {
            throw new InvalidArgumentException('Tên Homepage theme phải có từ 1 đến 80 ký tự.');
        }

        return $name;
    }

    private function requireTheme(array $themes, string $slug): void
    {
        if ($slug === '' || ! is_array($themes[$slug] ?? null)) {
            throw new InvalidArgumentException('Hãy chọn Homepage theme hợp lệ.');
        }
    }

    private function persist(array $themes): void
    {
        $this->settings->updateMany([self::SETTING_KEY => $themes], 'homepage');
    }
}
