<?php

namespace Modules\Finance\Services;

/**
 * CP23: conservative guard for destructive, opt-in scratch-table DB tests.
 * Never accepts remote hosts, sockets, ambiguous DSNs or production names.
 */
final class FinanceDebtTestDatabaseGuard
{
    public function inspect(string $dsn, string $authorization): array
    {
        if ($authorization !== 'yes') {
            return ['allowed' => false, 'reason_code' => 'explicit_opt_in_required'];
        }

        if (! preg_match('/^(mysql|pgsql):(.+)$/D', $dsn, $parts)) {
            return ['allowed' => false, 'reason_code' => 'unsupported_driver'];
        }

        $settings = [];
        foreach (explode(';', $parts[2]) as $part) {
            if (! preg_match('/^([a-z_]+)=([^;]+)$/D', $part, $pair)
                || array_key_exists($pair[1], $settings)) {
                return ['allowed' => false, 'reason_code' => 'ambiguous_dsn'];
            }
            $settings[$pair[1]] = $pair[2];
        }

        $allowedKeys = ['host', 'port', 'dbname'];
        if (array_diff(array_keys($settings), $allowedKeys)
            || ! isset($settings['host'], $settings['dbname'])
            || ! in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)
            || ! preg_match('/^[a-z][a-z0-9_]*_test$/D', $settings['dbname'])
            || (isset($settings['port']) && ! preg_match('/^[0-9]{2,5}$/D', $settings['port']))) {
            return ['allowed' => false, 'reason_code' => 'unsafe_database_target'];
        }

        return [
            'allowed' => true,
            'reason_code' => 'isolated_local_test_target',
            'driver' => $parts[1],
            'database' => $settings['dbname'],
        ];
    }

    public function assertAllowed(string $dsn, string $authorization): void
    {
        $result = $this->inspect($dsn, $authorization);
        if (! $result['allowed']) {
            throw new \RuntimeException('CP23 database test blocked: '.$result['reason_code']);
        }
    }
}
