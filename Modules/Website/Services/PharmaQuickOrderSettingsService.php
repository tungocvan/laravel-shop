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

    public function save(bool $enabled, array $priceListIds, ?string $notificationEmail = null): void
    {
        $this->settings->set(self::KEY, $this->normalize([
            'enabled' => $enabled,
            'price_list_ids' => $priceListIds,
            'notification_email' => $notificationEmail,
        ]), 'website', 'json');
    }

    public function parseNotificationEmails(?string $input): array
    {
        $addresses = preg_split('/[;,]+/', (string) $input) ?: [];

        return array_values(array_unique(array_filter(
            array_map(static fn (string $email): string => trim($email), $addresses),
            static fn (string $email): bool => $email !== ''
        )));
    }

    public function normalize(array $value): array
    {
        return [
            'enabled' => ($value['enabled'] ?? false) === true,
            'notification_email' => implode('; ', $this->parseNotificationEmails($value['notification_email'] ?? '')),
            'price_list_ids' => array_values(array_unique(array_filter(
                array_map('intval', is_array($value['price_list_ids'] ?? null) ? $value['price_list_ids'] : []),
                static fn (int $id): bool => $id > 0
            ))),
        ];
    }
}
