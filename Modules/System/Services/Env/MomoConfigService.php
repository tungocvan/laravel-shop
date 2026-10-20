<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\System\Services\SettingsService;
use RuntimeException;
use Throwable;

class MomoConfigService
{
    private const DEFAULT_ENDPOINT = 'https://test-payment.momo.vn';

    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function publicValues(): array
    {
        return [
            'MOMO_ENDPOINT' => $this->read('momo.endpoint', self::DEFAULT_ENDPOINT),
            'MOMO_PARTNER_CODE' => $this->read('momo.partner_code'),
            'MOMO_ACCESS_KEY' => '',
            'MOMO_SECRET_KEY' => '',
        ];
    }

    /**
     * Missing settings use safe display defaults. Never fall back to .env.
     */
    private function read(string $key, string $default = ''): string
    {
        try {
            $value = $this->settings->get($key, $default);
            return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
        } catch (Throwable $e) {
            Log::warning('MoMo setting unavailable.', ['key' => $key, 'exception' => $e::class]);
            return $default;
        }
    }

    /**
     * Trusted payment consumers must use this method instead of env().
     * A missing or unreadable secret is returned empty, never fabricated.
     */
    public function credentials(): array
    {
        return [
            'endpoint' => $this->read('momo.endpoint', self::DEFAULT_ENDPOINT),
            'partner_code' => $this->read('momo.partner_code'),
            'access_key' => $this->secret('momo.access_key'),
            'secret_key' => $this->secret('momo.secret_key'),
        ];
    }

    private function secret(string $key): string
    {
        $stored = $this->read($key);
        if ($stored === '') {
            return '';
        }
        if (!str_starts_with($stored, 'encrypted:')) {
            Log::warning('MoMo secret has invalid storage format.', ['key' => $key]);
            return '';
        }
        try {
            return Crypt::decryptString(substr($stored, strlen('encrypted:')));
        } catch (Throwable $e) {
            Log::warning('MoMo secret cannot be decrypted.', ['key' => $key, 'exception' => $e::class]);
            return '';
        }
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

        $lock = Cache::lock('system:momo-config:update', 10);
        if (!$lock->get()) {
            throw new RuntimeException('MoMo configuration update is already in progress.');
        }

        try {
            $values = [
                'momo.endpoint' => $endpoint,
                'momo.partner_code' => (string) ($form['MOMO_PARTNER_CODE'] ?? ''),
            ];
            foreach (['MOMO_ACCESS_KEY' => 'momo.access_key', 'MOMO_SECRET_KEY' => 'momo.secret_key'] as $field => $key) {
                $secret = (string) ($form[$field] ?? '');
                if ($secret !== '') {
                    $values[$key] = 'encrypted:'.Crypt::encryptString($secret);
                }
            }
            DB::transaction(fn () => $this->settings->updateMany($values, 'momo'));

            return true;
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
