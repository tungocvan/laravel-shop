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
        $footer = $config['footer'] ?? [];

        return [
            'enabled' => (bool) data_get($config, 'layout.show_footer', false),
            'components' => array_values(array_filter([
                $this->component('app_name', (bool) data_get($footer, 'show_app_name', true), [
                    'text' => (string) config('app.name', 'INAFO Pharma'),
                ]),
                $this->component('environment', (bool) data_get($footer, 'show_environment', true), [
                    'text' => (string) app()->environment(),
                ]),
            ])),
        ];
    }

    private function component(string $key, bool $enabled, array $data = []): ?array
    {
        if (! $enabled) {
            return null;
        }

        return [
            'key' => $key,
            'view' => "Admin::layouts.partials.footer.components.{$key}",
            'data' => $data,
        ];
    }
}
