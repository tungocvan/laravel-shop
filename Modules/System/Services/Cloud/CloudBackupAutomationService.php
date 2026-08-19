<?php

namespace Modules\System\Services\Cloud;

use Modules\System\Services\SettingsService;

class CloudBackupAutomationService
{
    private const GROUP = 'cloud_storage';

    public function __construct(private readonly SettingsService $settings) {}

    public function config(): array
    {
        return [
            'enabled' => filter_var($this->settings->get('cloud.google_drive.auto.enabled', false), FILTER_VALIDATE_BOOL),
            'time' => (string) $this->settings->get('cloud.google_drive.auto.time', '02:00'),
            'upload_drive' => filter_var($this->settings->get('cloud.google_drive.auto.upload_drive', true), FILTER_VALIDATE_BOOL),
            'local_retention' => max(1, (int) $this->settings->get('cloud.google_drive.auto.local_retention', 30)),
            'drive_retention' => max(1, (int) $this->settings->get('cloud.google_drive.auto.drive_retention', 30)),
            'last_run_date' => (string) $this->settings->get('cloud.google_drive.auto.last_run_date', ''),
            'last_run_at' => (string) $this->settings->get('cloud.google_drive.auto.last_run_at', ''),
            'last_status' => (string) $this->settings->get('cloud.google_drive.auto.last_status', ''),
            'last_message' => (string) $this->settings->get('cloud.google_drive.auto.last_message', ''),
        ];
    }

    public function save(array $config): void
    {
        $this->settings->set('cloud.google_drive.auto.enabled', (bool) ($config['enabled'] ?? false), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.time', (string) ($config['time'] ?? '02:00'), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.upload_drive', (bool) ($config['upload_drive'] ?? true), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.local_retention', max(1, (int) ($config['local_retention'] ?? 30)), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.drive_retention', max(1, (int) ($config['drive_retention'] ?? 30)), self::GROUP);
    }

    public function dueNow(): bool
    {
        $config = $this->config();
        if (! $config['enabled'] || $config['last_run_date'] === now()->toDateString()) {
            return false;
        }

        return now()->format('H:i') === $config['time'];
    }

    public function markRun(string $status, string $message): void
    {
        $this->settings->set('cloud.google_drive.auto.last_run_date', now()->toDateString(), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.last_run_at', now()->toIso8601String(), self::GROUP);
        $this->settings->set('cloud.google_drive.auto.last_status', $status, self::GROUP);
        $this->settings->set('cloud.google_drive.auto.last_message', mb_substr($message, 0, 1000), self::GROUP);
    }
}
