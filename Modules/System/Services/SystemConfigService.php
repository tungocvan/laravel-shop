<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Modules\System\Support\IconParser;

class SystemConfigService
{
    private const DISABLED_PRODUCTION_COMPONENTS = [
        'system.settings.artisan-list',
        'system.settings.sh-script',
    ];

    protected string $corePath;
    protected string $overridePath;

    public function __construct()
    {
        $this->corePath = base_path('Modules/System/config/system_tabs.php');
        $this->overridePath = base_path('Modules/System/data/system_tabs.json');
    }
    public function getTabs(): array
    {
        $coreTime = file_exists($this->corePath)
            ? filemtime($this->corePath)
            : 'no-core';

        $overrideTime = file_exists($this->overridePath)
            ? filemtime($this->overridePath)
            : 'no-override';

        $cacheKey = "system_tabs_{$coreTime}_{$overrideTime}";

        return Cache::remember($cacheKey, 3600, function () {
            return $this->normalize(
                $this->mergeById(
                    $this->getCore(),
                    $this->getOverride()
                )
            );
        });
    }
    protected function getCore(): array
    {
        if (!File::exists($this->corePath)) {
            return [];
        }

        return File::getRequire($this->corePath);
    }

    protected function getOverride(): array
    {
        if (!File::exists($this->overridePath)) {
            return [];
        }

        $data = json_decode(File::get($this->overridePath), true);

        return is_array($data) ? $data : [];
    }

    protected function mergeById(array $core, array $override): array
    {
        $result = [];

        foreach ($core as $item) {
            if (!isset($item['id'])) continue;
            $result[$item['id']] = $item;
        }

        foreach ($override as $item) {
            if (!isset($item['id'])) continue;

            $result[$item['id']] = array_merge(
                $result[$item['id']] ?? [],
                $item
            );
        }

        return array_values($result);
    }

    // =========================
    // UPDATE 1 TAB
    // =========================
    public function updateTab(string $id, array $data): void
    {
        $tabs = collect($this->getOverride())
            ->keyBy('id');

        $tabs[$id] = array_merge(
            $tabs[$id] ?? [],
            ['id' => $id],
            $data
        );

        $this->save($tabs->values()->toArray());
    }

    // =========================
    // SAVE JSON
    // =========================
    public function save(array $tabs): void
    {
        File::put(
            $this->overridePath,
            json_encode($tabs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->clearCache();
    }

    // =========================
    // RESET OVERRIDE
    // =========================
    public function reset(): void
    {
        if (File::exists($this->overridePath)) {
            File::delete($this->overridePath);
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget('system_tabs');
    }
    protected function normalize(array $tabs): array
    {
        return collect($tabs)->map(function ($tab) {

            $tab['icon'] = IconParser::parse($tab['icon'] ?? null)
                ?? 'M4 6h16M4 12h16M4 18h16'; // default

            if (in_array($tab['component'] ?? null, self::DISABLED_PRODUCTION_COMPONENTS, true)) {
                $tab['enabled'] = false;
            }

            return $tab;
        })->toArray();
    }
}
