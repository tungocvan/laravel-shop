<?php

namespace Modules\Invoices\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GdtApiService
{
    public function hasToken(): bool
    {
        return Cache::has(config('invoices.gdt.cache_key'));
    }

    public function forgetToken(): void
    {
        Cache::forget(config('invoices.gdt.cache_key'));
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
