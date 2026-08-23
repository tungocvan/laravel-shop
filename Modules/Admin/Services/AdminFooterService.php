<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminFooterService
{
    public function __construct(
        private readonly AdminLayoutManager $layoutManager,
    ) {
    }

    public function context(): array
    {
        $config = $this->layoutManager->config();
        $footer = (array) data_get($config, 'footer', []);
        $copyright = (array) data_get($footer, 'copyright', []);
        $dateTime = (array) data_get($footer, 'datetime', []);
        $presentation = (array) data_get($footer, 'presentation', []);
        $now = now();
        $startYear = data_get($copyright, 'start_year');
        $currentYear = (int) $now->format('Y');

        return [
            'enabled' => (bool) data_get($config, 'layout.show_footer', false),
            'presentation' => [
                'alignment' => data_get($presentation, 'alignment', 'split'),
                'background' => data_get($presentation, 'background', 'system'),
                'divider' => data_get($presentation, 'divider', 'subtle'),
                'compact' => (bool) data_get($presentation, 'compact', true),
            ],
            'components' => array_values(array_filter([
                $this->component('copyright', (bool) data_get($copyright, 'enabled', true), [
                    'showAppName' => (bool) data_get($footer, 'show_app_name', true),
                    'appName' => (string) config('app.name', 'INAFO Pharma'),
                    'owner' => trim((string) data_get($copyright, 'owner', '')),
                    'url' => data_get($copyright, 'url'),
                    'year' => $this->copyrightYear($startYear, $currentYear),
                ]),
                $this->component('datetime', (bool) data_get($dateTime, 'show_date', true) || (bool) data_get($dateTime, 'show_time', true), [
                    'showDate' => (bool) data_get($dateTime, 'show_date', true),
                    'showTime' => (bool) data_get($dateTime, 'show_time', true),
                    'date' => $now->format('d/m/Y'),
                    'time' => $now->format('H:i:s'),
                ]),
            ])),
        ];
    }

    private function copyrightYear(mixed $startYear, int $currentYear): string
    {
        $startYear = is_numeric($startYear) ? (int) $startYear : null;
        if (! $startYear || $startYear >= $currentYear) {
            return (string) $currentYear;
        }

        return "{$startYear}–{$currentYear}";
    }

    private function component(string $key, bool $enabled, array $data = []): ?array
    {
        if (! $enabled) {
            return null;
        }

        return ['key' => $key, 'view' => "Admin::layouts.partials.footer.components.{$key}", 'data' => $data];
    }
}
