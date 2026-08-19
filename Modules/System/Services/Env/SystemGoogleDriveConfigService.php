<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SystemGoogleDriveConfigService
{
    private const PUBLIC_KEYS = [
        'GOOGLE_DRIVE_CLIENT_ID',
        'GOOGLE_DRIVE_REDIRECT_URI',
        'GOOGLE_DRIVE_FOLDER_NAME',
    ];

    private const WRITABLE_KEYS = [
        'GOOGLE_DRIVE_CLIENT_ID',
        'GOOGLE_DRIVE_CLIENT_SECRET',
        'GOOGLE_DRIVE_REDIRECT_URI',
        'GOOGLE_DRIVE_FOLDER_NAME',
    ];

    public function __construct(private readonly EnvManagerService $envManager) {}

    public function publicConfig(): array
    {
        $env = $this->envManager->getValues();

        return [
            'GOOGLE_DRIVE_CLIENT_ID' => $env['GOOGLE_DRIVE_CLIENT_ID'] ?? '',
            'GOOGLE_DRIVE_REDIRECT_URI' => $env['GOOGLE_DRIVE_REDIRECT_URI'] ?? route('admin.system.settings.cloud.google.callback'),
            'GOOGLE_DRIVE_FOLDER_NAME' => $env['GOOGLE_DRIVE_FOLDER_NAME'] ?? 'Laravel-Backup',
        ];
    }

    public function isConfigured(): bool
    {
        $env = $this->envManager->getValues();

        return trim((string) ($env['GOOGLE_DRIVE_CLIENT_ID'] ?? '')) !== ''
            && trim((string) ($env['GOOGLE_DRIVE_CLIENT_SECRET'] ?? '')) !== ''
            && trim((string) ($env['GOOGLE_DRIVE_REDIRECT_URI'] ?? '')) !== '';
    }

    public function save(array $form, ?int $actorId = null): array
    {
        $candidate = $this->resolveCandidate($form);
        $lock = Cache::lock('system:google-drive-config:update', 15);

        if (! $lock->get()) {
            return ['success' => false, 'message' => 'Một thao tác cập nhật Google Drive khác đang được thực hiện.'];
        }

        try {
            if (! $this->envManager->update($candidate)) {
                throw new RuntimeException('Environment update returned false.');
            }

            Artisan::call('config:clear');
            Log::notice('Google Drive configuration saved.', [
                'actor_id' => $actorId,
                'client_id_configured' => $candidate['GOOGLE_DRIVE_CLIENT_ID'] !== '',
                'client_secret_replaced' => trim((string) ($form['GOOGLE_DRIVE_CLIENT_SECRET'] ?? '')) !== '',
                'redirect_uri' => $candidate['GOOGLE_DRIVE_REDIRECT_URI'],
                'folder_name' => $candidate['GOOGLE_DRIVE_FOLDER_NAME'],
            ]);

            return ['success' => true, 'message' => 'Cấu hình Google Drive đã được lưu.'];
        } catch (Throwable $e) {
            Log::error('Google Drive configuration save failed.', [
                'actor_id' => $actorId,
                'exception' => $e::class,
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function resolveCandidate(array $form): array
    {
        if (array_diff(array_keys($form), self::WRITABLE_KEYS) !== []) {
            throw new InvalidArgumentException('Unsupported Google Drive configuration key.');
        }

        $env = $this->envManager->getValues();
        $candidate = [];

        foreach (self::WRITABLE_KEYS as $key) {
            $candidate[$key] = trim((string) ($form[$key] ?? ''));
        }

        if ($candidate['GOOGLE_DRIVE_CLIENT_SECRET'] === '') {
            $candidate['GOOGLE_DRIVE_CLIENT_SECRET'] = (string) ($env['GOOGLE_DRIVE_CLIENT_SECRET'] ?? '');
        }

        if ($candidate['GOOGLE_DRIVE_FOLDER_NAME'] === '') {
            $candidate['GOOGLE_DRIVE_FOLDER_NAME'] = 'Laravel-Backup';
        }

        if ($candidate['GOOGLE_DRIVE_CLIENT_ID'] === '' || $candidate['GOOGLE_DRIVE_REDIRECT_URI'] === '') {
            throw new InvalidArgumentException('Google Drive Client ID và Redirect URI là bắt buộc.');
        }

        return $candidate;
    }
}
