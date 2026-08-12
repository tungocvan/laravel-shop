<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacySettingsMigrationService
{
    public function migrate(bool $apply = false): array
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('wp_settings')) {
            throw new \RuntimeException('Cần tồn tại cả hai bảng settings và wp_settings.');
        }

        $allLegacyRows = DB::table('wp_settings')
            ->orderBy('key')
            ->get(['key', 'value', 'group_name', 'type', 'label']);
        $legacyRows = $allLegacyRows
            ->reject(fn (object $row): bool => str_starts_with((string) $row->key, 'home_'))
            ->values();
        $canonical = DB::table('settings')->whereIn('key', $legacyRows->pluck('key'))->get()->keyBy('key');
        $result = ['inserted' => 0, 'identical' => 0, 'conflicts' => 0, 'skipped_homepage' => 0];
        $candidates = [];

        foreach ($legacyRows as $legacy) {
            $current = $canonical->get($legacy->key);

            if (! $current) {
                $candidates[] = $legacy;
            } elseif ($this->equivalent($current, $legacy)) {
                $result['identical']++;
            } else {
                $result['conflicts']++;
            }
        }

        $result['inserted'] = count($candidates);
        $result['skipped_homepage'] = $allLegacyRows
            ->filter(fn (object $row): bool => str_starts_with((string) $row->key, 'home_'))
            ->count();

        if ($apply && $candidates) {
            DB::transaction(function () use ($candidates): void {
                foreach ($candidates as $legacy) {
                    DB::table('settings')->insert([
                        'key' => $legacy->key,
                        'value' => $legacy->value,
                        'group_name' => $legacy->group_name,
                        'type' => $legacy->type,
                        'label' => $legacy->label,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            foreach ($candidates as $legacy) {
                Cache::forget('system.setting.'.$legacy->key);
                Cache::forget('wp_opt_'.$legacy->key);
                Cache::forget('setting_'.$legacy->key);
            }
        }

        $result['applied'] = $apply;

        return $result;
    }

    private function equivalent(object $canonical, object $legacy): bool
    {
        return (string) $canonical->value === (string) $legacy->value
            && (string) $canonical->group_name === (string) $legacy->group_name
            && (string) $canonical->type === (string) $legacy->type;
    }
}
