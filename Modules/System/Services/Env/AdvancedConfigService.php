<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

class AdvancedConfigService
{
    private const QUEUE_DRIVERS = ['sync', 'database', 'redis'];

    public function __construct(private readonly EnvManagerService $envManager)
    {
    }

    public function publicValues(): array
    {
        $env = $this->envManager->getValues();

        return [
            'QUEUE_CONNECTION' => $env['QUEUE_CONNECTION'] ?? 'database',
            'NODEJS_SERVER_URL' => $env['NODEJS_SERVER_URL'] ?? 'http://127.0.0.1:3000',
            'BRIDGE_SECRET_KEY' => '',
        ];
    }

    public function resolveForOperation(array $form): array
    {
        $env = $this->envManager->getValues();

        return [
            'url' => (string) ($form['NODEJS_SERVER_URL'] ?? ''),
            'secret' => ($form['BRIDGE_SECRET_KEY'] ?? '') !== ''
                ? (string) $form['BRIDGE_SECRET_KEY']
                : (string) ($env['BRIDGE_SECRET_KEY'] ?? ''),
        ];
    }

    public function save(array $form): bool
    {
        $driver = (string) ($form['QUEUE_CONNECTION'] ?? '');
        if (!in_array($driver, self::QUEUE_DRIVERS, true)) {
            throw new InvalidArgumentException('Unsupported queue connection.');
        }

        $current = $this->envManager->getValues();
        $data = [
            'QUEUE_CONNECTION' => $driver,
            'NODEJS_SERVER_URL' => (string) ($form['NODEJS_SERVER_URL'] ?? ''),
            'BRIDGE_SECRET_KEY' => ($form['BRIDGE_SECRET_KEY'] ?? '') !== ''
                ? (string) $form['BRIDGE_SECRET_KEY']
                : (string) ($current['BRIDGE_SECRET_KEY'] ?? ''),
        ];

        $lock = Cache::lock('system:advanced-config:update', 10);
        if (!$lock->get()) {
            throw new RuntimeException('System configuration update is already in progress.');
        }

        try {
            return $this->envManager->update($data);
        } finally {
            $lock->release();
        }
    }
}
