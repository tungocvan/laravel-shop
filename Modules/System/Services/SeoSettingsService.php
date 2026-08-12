<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\System\Models\Setting;

class SeoSettingsService
{
    private const KEYS = [
        'seo_title',
        'seo_description',
        'social_facebook',
        'social_zalo',
        'header_script',
    ];

    public function all(): array
    {
        return collect(self::KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => Setting::getValue($key, '') ?? ''])
            ->all();
    }

    public function save(array $values, ?int $actorId = null): void
    {
        $data = collect(self::KEYS)
            ->mapWithKeys(function (string $key) use ($values): array {
                $value = $values[$key] ?? '';
                $value = is_string($value) ? trim($value) : '';

                if ($key === 'seo_description') {
                    $value = $this->normalizeDescription($value);
                }

                return [$key => $value];
            })
            ->all();

        $previousHeader = (string) Setting::getValue('header_script', '');

        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'group_name' => 'seo',
                        'type' => $key === 'header_script' ? 'code' : 'text',
                    ],
                );
            }
        });

        foreach (self::KEYS as $key) {
            $this->forgetCaches($key);
        }

        $header = (string) $data['header_script'];
        $headerChanged = hash('sha256', $previousHeader) !== hash('sha256', $header);

        Log::notice('SEO settings saved.', [
            'actor_id' => $actorId,
            'header_script_changed' => $headerChanged,
            'header_script_length' => mb_strlen($header),
            'header_script_sha256' => hash('sha256', $header),
        ]);
    }

    private function normalizeDescription(string $value): string
    {
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function forgetCaches(string $key): void
    {
        Cache::forget('setting_'.$key);
        Cache::forget('wp_opt_'.$key);
    }
}
