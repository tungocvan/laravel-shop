<?php

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\System\Services\Env\EnvManagerService;
use RuntimeException;
use Throwable;

class DatabaseConfigService
{
    private const DRIVERS = ['mysql', 'pgsql'];

    private const PUBLIC_KEYS = [
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
    ];

    public function __construct(
        private readonly EnvManagerService $envManager,
        private readonly DbConnectionService $dbConnection,
    ) {
    }

    public function publicConfig(): array
    {
        $env = $this->envManager->getValues();

        return [
            'DB_CONNECTION' => $env['DB_CONNECTION'] ?? 'mysql',
            'DB_HOST' => $env['DB_HOST'] ?? '127.0.0.1',
            'DB_PORT' => $env['DB_PORT'] ?? '3306',
            'DB_DATABASE' => $env['DB_DATABASE'] ?? '',
            'DB_USERNAME' => $env['DB_USERNAME'] ?? '',
        ];
    }

    public function test(array $form, ?int $actorId = null): array
    {
        $candidate = $this->candidate($form);
        $result = $this->dbConnection->testConnection($candidate);

        Log::notice('Database configuration connection tested.', [
            'actor_id' => $actorId,
            'driver' => $candidate['DB_CONNECTION'],
            'password_replaced' => trim((string) ($form['DB_PASSWORD'] ?? '')) !== '',
            'success' => (bool) ($result['success'] ?? false),
        ]);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'message' => (bool) ($result['success'] ?? false)
                ? 'Kết nối cơ sở dữ liệu thành công.'
                : 'Không thể kết nối cơ sở dữ liệu với cấu hình đã nhập.',
        ];
    }

    public function save(array $form, ?int $actorId = null): array
    {
        $lock = Cache::lock('system:database-config:update', 30);

        try {
            return $lock->block(5, function () use ($form, $actorId): array {
                $candidate = $this->candidate($form);
                $test = $this->dbConnection->testConnection($candidate);

                if (! ($test['success'] ?? false)) {
                    return [
                        'success' => false,
                        'message' => 'Không thể lưu vì kết nối cơ sở dữ liệu thất bại.',
                    ];
                }

                if (! $this->envManager->update($candidate)) {
                    throw new RuntimeException('Database environment update failed.');
                }

                $exitCode = Artisan::call('config:clear');
                if ($exitCode !== 0) {
                    throw new RuntimeException('Laravel configuration cache clear failed.');
                }

                Log::notice('Database configuration updated.', [
                    'actor_id' => $actorId,
                    'driver' => $candidate['DB_CONNECTION'],
                    'password_replaced' => trim((string) ($form['DB_PASSWORD'] ?? '')) !== '',
                ]);

                return [
                    'success' => true,
                    'message' => 'Cấu hình Database đã được cập nhật và sao lưu an toàn.',
                ];
            });
        } catch (Throwable $e) {
            Log::error('Database configuration update failed.', [
                'actor_id' => $actorId,
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    private function candidate(array $form): array
    {
        $driver = strtolower(trim((string) ($form['DB_CONNECTION'] ?? '')));
        if (! in_array($driver, self::DRIVERS, true)) {
            throw new InvalidArgumentException('Unsupported database driver.');
        }

        $env = $this->envManager->getValues();
        $replacementPassword = (string) ($form['DB_PASSWORD'] ?? '');
        $password = $replacementPassword === ''
            ? (string) ($env['DB_PASSWORD'] ?? '')
            : $replacementPassword;

        $candidate = [
            'DB_CONNECTION' => $driver,
            'DB_HOST' => trim((string) ($form['DB_HOST'] ?? '')),
            'DB_PORT' => (string) ($form['DB_PORT'] ?? ''),
            'DB_DATABASE' => trim((string) ($form['DB_DATABASE'] ?? '')),
            'DB_USERNAME' => trim((string) ($form['DB_USERNAME'] ?? '')),
            'DB_PASSWORD' => $password,
        ];

        foreach (self::PUBLIC_KEYS as $key) {
            if ($candidate[$key] === '') {
                throw new InvalidArgumentException("Missing required database setting: {$key}");
            }
        }

        $port = filter_var($candidate['DB_PORT'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($port === false) {
            throw new InvalidArgumentException('Invalid database port.');
        }

        $candidate['DB_PORT'] = (string) $port;

        return $candidate;
    }
}
