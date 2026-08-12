<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MomoConfigService
{
    public function __construct(private readonly EnvManagerService $envManager)
    {
    }

    public function publicValues(): array
    {
        $env = $this->envManager->getValues();

        return [
            'MOMO_ENDPOINT' => $env['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn',
            'MOMO_PARTNER_CODE' => $env['MOMO_PARTNER_CODE'] ?? '',
            'MOMO_ACCESS_KEY' => '',
            'MOMO_SECRET_KEY' => '',
        ];
    }

    public function testEndpoint(string $endpoint): array
    {
        $this->assertEndpoint($endpoint);

        try {
            $response = Http::connectTimeout(2)->timeout(5)->get($endpoint);
            return $response->successful()
                ? ['success' => true, 'message' => 'Endpoint MoMo hoạt động.']
                : ['success' => false, 'message' => 'Endpoint MoMo không phản hồi trạng thái hợp lệ.'];
        } catch (Throwable $e) {
            Log::warning('MoMo endpoint test failed.', ['exception' => $e::class]);
            return ['success' => false, 'message' => 'Không thể kết nối endpoint MoMo. Vui lòng kiểm tra log hệ thống.'];
        }
    }

    public function save(array $form): bool
    {
        $endpoint = (string) ($form['MOMO_ENDPOINT'] ?? '');
        $this->assertEndpoint($endpoint);
        $current = $this->envManager->getValues();

        $data = [
            'MOMO_ENDPOINT' => $endpoint,
            'MOMO_PARTNER_CODE' => (string) ($form['MOMO_PARTNER_CODE'] ?? ''),
            'MOMO_ACCESS_KEY' => ($form['MOMO_ACCESS_KEY'] ?? '') !== '' ? (string) $form['MOMO_ACCESS_KEY'] : (string) ($current['MOMO_ACCESS_KEY'] ?? ''),
            'MOMO_SECRET_KEY' => ($form['MOMO_SECRET_KEY'] ?? '') !== '' ? (string) $form['MOMO_SECRET_KEY'] : (string) ($current['MOMO_SECRET_KEY'] ?? ''),
        ];

        $lock = Cache::lock('system:momo-config:update', 10);
        if (!$lock->get()) {
            throw new RuntimeException('MoMo configuration update is already in progress.');
        }

        try {
            return $this->envManager->update($data);
        } finally {
            $lock->release();
        }
    }

    private function assertEndpoint(string $endpoint): void
    {
        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || $host === '' || !($host === 'momo.vn' || str_ends_with($host, '.momo.vn'))) {
            throw new InvalidArgumentException('MoMo endpoint must use an approved HTTPS momo.vn host.');
        }
    }
}
