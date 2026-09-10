<?php

namespace Modules\Invoices\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GdtApiService
{
    public function hasToken(): bool
    {
        if (! Cache::has(config('invoices.gdt.cache_key'))) {
            return false;
        }

        try {
            $this->assertTokenUsable();

            return true;
        } catch (RuntimeException $exception) {
            Log::warning('GDT token preflight failed.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function forgetToken(): void
    {
        Cache::forget(config('invoices.gdt.cache_key'));
    }

    /**
     * Verify that the cached token is accepted by GDT before a bulk sync is queued.
     */
    public function assertTokenUsable(): void
    {
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new RuntimeException('Phiên đăng nhập GDT chưa được tạo hoặc đã hết hạn.');
        }

        $today = now()->format('d/m/Y');
        $search = "tdlap=ge={$today}T00:00:00;tdlap=le={$today}T23:59:59";

        try {
            $response = $this->client()
                ->withToken($token)
                ->acceptJson()
                ->get($this->url('/query/invoices/purchase'), [
                    'sort' => 'tdlap:desc',
                    'size' => 1,
                    'search' => $search,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kiểm tra phiên GDT do mất kết nối.', previous: $exception);
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->forgetToken();
            throw new RuntimeException('Phiên đăng nhập GDT đã hết hạn. Vui lòng kết nối lại trước khi đồng bộ.');
        }

        if (! $response->successful()) {
            throw new RuntimeException("Không thể xác minh phiên GDT: HTTP {$response->status()}.");
        }
    }

    public function loadCaptcha(): array
    {
        try {
            $response = $this->client()->get($this->url('/captcha'));
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API GDT để tải captcha.', [
                'url' => $this->url('/captcha'),
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('API GDT trả lỗi khi tải captcha.', [
                'url' => $this->url('/captcha'),
                'status' => $response->status(),
            ]);

            return [];
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Authenticate & lấy token
     */
    public function login(string $cvalue, string $ckey, int $time = 1800): array
    {
        $username = config('invoices.gdt.username');
        $password = config('invoices.gdt.password');

        if (! $username || ! $password) {
            Log::error('Chưa cấu hình GDT_API_USERNAME hoặc GDT_API_PASSWORD.');

            return [
                'status' => 'error',
                'message' => 'Chưa cấu hình tài khoản GDT.',
            ];
        }

        try {
            $res = $this->client()->post($this->url('/security-taxpayer/authenticate'), [
                'username' => $username,
                'password' => $password,
                'ckey' => $ckey,
                'cvalue' => $cvalue,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API GDT để đăng nhập.', [
                'url' => $this->url('/security-taxpayer/authenticate'),
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Không thể kết nối đến hệ thống GDT.',
            ];
        }

        if ($res->successful()) {
            $token = $res->json('token') ?? ($res->json('accessToken') ?? null);

            if ($token) {
                Cache::put(config('invoices.gdt.cache_key'), $token, $time);
            }

            return [
                'status' => $token ? 'success' : 'error',
                'message' => $token ? null : 'GDT không trả về token.',
            ];
        }

        return [
            'status' => 'error',
            'message' => $res->json('message') ?? 'Đăng nhập GDT không thành công.',
        ];
    }

    private function client()
    {
        return Http::withOptions([
            'verify' => (bool) config('invoices.gdt.verify_ssl', true),
        ])->timeout((int) config('invoices.gdt.timeout', 15));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('invoices.gdt.base_url'), '/').'/'.ltrim($path, '/');
    }
}
