<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacySettingsAuditService
{
    public function audit(): array
    {
        $canonical = $this->rows('settings');
        $legacy = $this->rows('wp_settings');
        $keys = $canonical->keys()->merge($legacy->keys())->unique()->sort()->values();
        $details = $keys->map(function (string $key) use ($canonical, $legacy): array {
            $current = $canonical->get($key);
            $old = $legacy->get($key);
            $status = match (true) {
                ! $current => 'legacy_only',
                ! $old => 'canonical_only',
                $this->equivalent($current, $old) => 'identical',
                default => 'conflict',
            };

            return [
                'key' => $key,
                'status' => $status,
                'destination' => str_starts_with($key, 'home_') ? 'structured_homepage' : 'settings',
                'canonical_group' => $current?->group_name,
                'legacy_group' => $old?->group_name,
                'canonical_type' => $current?->type,
                'legacy_type' => $old?->type,
            ];
        });

        return [
            'summary' => $details->countBy('status')->all() + [
                'total' => $details->count(),
                'structured_homepage' => $details->where('destination', 'structured_homepage')->count(),
            ],
            'details' => $details->all(),
        ];
    }

    private function rows(string $table)
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->select('key', 'value', 'group_name', 'type')
            ->orderBy('key')
            ->get()
            ->keyBy('key');
    }

    private function equivalent(object $canonical, object $legacy): bool
    {
        return (string) $canonical->value === (string) $legacy->value
            && (string) $canonical->group_name === (string) $legacy->group_name
            && (string) $canonical->type === (string) $legacy->type;
    }
}
