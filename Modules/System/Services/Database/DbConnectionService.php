<?php

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class DbConnectionService
{
    public function testConnection(array $config): array
    {
        $driver = strtolower((string) ($config['DB_CONNECTION'] ?? ''));
        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            throw new InvalidArgumentException('Unsupported database driver.');
        }

        $tempConnection = 'temp_test_connection';
        $connectionConfig = $this->connectionConfig($driver, $config);

        Config::set("database.connections.{$tempConnection}", $connectionConfig);

        try {
            DB::connection($tempConnection)->getPdo();

            return [
                'success' => true,
                'message' => 'Kết nối cơ sở dữ liệu thành công.',
            ];
        } catch (Throwable $e) {
            Log::warning('Temporary database connection test failed.', [
                'driver' => $driver,
                'exception' => $e::class,
            ]);

            return [
                'success' => false,
                'message' => 'Không thể kết nối cơ sở dữ liệu với cấu hình đã nhập.',
            ];
        } finally {
            DB::purge($tempConnection);
            Config::set("database.connections.{$tempConnection}", null);
        }
    }

    private function connectionConfig(string $driver, array $config): array
    {
        $base = [
            'driver' => $driver,
            'host' => (string) $config['DB_HOST'],
            'port' => (string) $config['DB_PORT'],
            'database' => (string) $config['DB_DATABASE'],
            'username' => (string) $config['DB_USERNAME'],
            'password' => (string) ($config['DB_PASSWORD'] ?? ''),
            'prefix' => '',
        ];

        if ($driver === 'pgsql') {
            return $base + [
                'charset' => 'utf8',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ];
        }

        return $base + [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
        ];
    }
}
