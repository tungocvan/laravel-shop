<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\System\Jobs\TestQueueJob;
use Throwable;

class SystemConfigService
{
    public function pingNodeJS(string $url, string $secret): array
    {
        $url = rtrim(trim($url), '/');
        $parts = parse_url($url);

        if (!is_array($parts) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('NodeJS URL is invalid.');
        }

        try {
            $response = Http::connectTimeout(2)
                ->timeout(5)
                ->withHeaders([
                    'x-bridge-secret' => $secret,
                    'Accept' => 'application/json',
                ])
                ->get($url . '/health');

            return $response->successful()
                ? ['success' => true, 'message' => 'Kết nối NodeJS thành công.']
                : ['success' => false, 'message' => 'NodeJS không phản hồi trạng thái hợp lệ.'];
        } catch (Throwable $e) {
            Log::warning('NodeJS bridge health check failed.', [
                'host' => $parts['host'] ?? null,
                'exception' => $e::class,
            ]);

            return ['success' => false, 'message' => 'Không thể kết nối NodeJS. Vui lòng kiểm tra log hệ thống.'];
        }
    }

    public function dispatchTestJob(): void
    {
        Cache::forget('queue_test_status');
        TestQueueJob::dispatch();
    }

    public function checkQueueStatus(): string
    {
        return Cache::get('queue_test_status', 'Pending...');
    }
}
