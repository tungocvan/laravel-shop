<?php

namespace Modules\Website\Services;

use Modules\System\Services\SettingsService;

final class PharmaQuickOrderSettingsService
{
    public const KEY = 'website.pharma_quick_order';

    public function __construct(private readonly SettingsService $settings) {}

    public function get(): array
    {
        $saved = $this->settings->get(self::KEY, []);

        return $this->normalize(is_array($saved) ? $saved : []);
    }

    public function save(bool $enabled, array $priceListIds): void
    {
        $this->settings->set(self::KEY, $this->normalize([
            'enabled' => $enabled,
            'price_list_ids' => $priceListIds,
        ]), 'website', 'json');
    }

    public function normalize(array $value): array
    {
        return [
            'enabled' => ($value['enabled'] ?? false) === true,
            'price_list_ids' => array_values(array_unique(array_filter(
                array_map('intval', is_array($value['price_list_ids'] ?? null) ? $value['price_list_ids'] : []),
                static fn (int $id): bool => $id > 0
            ))),
        ];
    }
}
