<?php

namespace Modules\Invoices\Services;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GdtApiService
{
    /**
     * Local cache-state check only. Do not call GDT from page rendering/status badges.
     * Remote validity is checked explicitly by assertTokenUsable() before synchronization.
     */
    public function hasToken(): bool
    {
        return Cache::has(config('invoices.gdt.cache_key'));
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
            Log::warning('GDT token preflight connection failure.', [
                'url' => $this->url('/query/invoices/purchase'),
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Không thể kiểm tra phiên GDT do mất kết nối.', previous: $exception);
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->forgetToken();
            throw new RuntimeException('Phiên đăng nhập GDT đã hết hạn. Vui lòng kết nối lại trước khi đồng bộ.');
        }

        if (! $response->successful()) {
            Log::warning('GDT token preflight rejected by upstream.', [
                'url' => $this->url('/query/invoices/purchase'),
                'status' => $response->status(),
                'message' => $this->responseMessage($response->json()),
            ]);

            throw new RuntimeException("Không thể xác minh phiên GDT: HTTP {$response->status()}.");
        }
    }

    public function loadCaptcha(): array
    {
        // Captcha and authenticate are a single upstream session. Start a fresh cookie jar
        // here and persist it because Livewire will submit authenticate in another PHP request.
        $cookies = new CookieJar();
        $startedAt = microtime(true);

        try {
            $response = $this->sessionClient($cookies)->get($this->url('/captcha'));
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API GDT để tải captcha.', [
                'url' => $this->url('/captcha'),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('API GDT trả lỗi khi tải captcha.', [
                'url' => $this->url('/captcha'),
                'status' => $response->status(),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
                'message' => $this->responseMessage($response->json()),
            ]);

            return [];
        }

        $this->persistSessionCookies($cookies);

        Log::info('GDT captcha session prepared.', [
            'url' => $this->url('/captcha'),
            'status' => $response->status(),
            'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            'cookie_count' => count($cookies->toArray()),
        ]);

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Authenticate & lấy token.
     * Diagnostic logging intentionally excludes username, password, captcha, cookie values and token.
     */
    public function login(string $cvalue, string $ckey, int $time = 1800): array
    {
        $username = config('invoices.gdt.username');
        $password = config('invoices.gdt.password');
        $url = $this->url('/security-taxpayer/authenticate');

        if (! $username || ! $password) {
            Log::error('Chưa cấu hình GDT_API_USERNAME hoặc GDT_API_PASSWORD.');

            return [
                'status' => 'error',
                'message' => 'Chưa cấu hình tài khoản GDT.',
            ];
        }

        $cookies = $this->restoreSessionCookies();
        if (count($cookies->toArray()) === 0) {
            Log::warning('GDT authenticate started without captcha session cookies.', [
                'url' => $url,
            ]);
        }

        $startedAt = microtime(true);

        try {
            // Authentication can legitimately take longer than list/detail reads during GDT peak load.
            // Do not blindly retry the POST because captcha/authenticate may not be safely repeatable.
            $res = $this->authenticationClient($cookies)->post($url, [
                'username' => $username,
                'password' => $password,
                'ckey' => $ckey,
                'cvalue' => $cvalue,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API GDT để đăng nhập.', [
                'url' => $url,
                'timeout_seconds' => $this->authenticationTimeout(),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
                'cookie_count' => count($cookies->toArray()),
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'GDT không phản hồi trong thời gian chờ. Hãy tải captcha mới và thử lại sau; hệ thống chưa xác định đây là lỗi tài khoản/mật khẩu.',
            ];
        }

        $this->persistSessionCookies($cookies);

        $payload = $res->json();
        $message = $this->responseMessage($payload);
        $diagnostics = [
            'url' => $url,
            'status' => $res->status(),
            'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            'cookie_count' => count($cookies->toArray()),
        ];

        if ($res->successful()) {
            $token = is_array($payload) ? ($payload['token'] ?? $payload['accessToken'] ?? null) : null;

            if ($token) {
                Cache::put(config('invoices.gdt.cache_key'), $token, $time);
                Cache::forget($this->sessionCacheKey());

                Log::notice('GDT login succeeded.', $diagnostics + [
                    'token_cached' => true,
                ]);

                return ['status' => 'success', 'message' => null];
            }

            Log::warning('GDT login response did not contain a token.', $diagnostics + [
                'message' => $message,
                'response_keys' => is_array($payload) ? array_keys($payload) : [],
            ]);

            return [
                'status' => 'error',
                'message' => $message ?: 'GDT xác thực thành công nhưng không trả về token.',
            ];
        }

        Log::warning('GDT login rejected.', $diagnostics + [
            'message' => $message,
            'response_keys' => is_array($payload) ? array_keys($payload) : [],
        ]);

        return [
            'status' => 'error',
            'message' => $message ?: "Đăng nhập GDT không thành công (HTTP {$res->status()}).",
        ];
    }

    private function responseMessage(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach (['message', 'error', 'detail', 'title'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return mb_substr(trim($value), 0, 1000);
            }
        }

        return null;
    }

    private function authenticationTimeout(): int
    {
        return max(30, min((int) config('invoices.gdt.auth_timeout', 45), 120));
    }

    private function authenticationClient(CookieJar $cookies)
    {
        return $this->baseClient($cookies)
            ->connectTimeout(min(15, $this->authenticationTimeout()))
            ->timeout($this->authenticationTimeout());
    }

    private function sessionClient(CookieJar $cookies)
    {
        return $this->baseClient($cookies)
            ->connectTimeout(min(10, (int) config('invoices.gdt.timeout', 15)))
            ->timeout((int) config('invoices.gdt.timeout', 15));
    }

    private function baseClient(CookieJar $cookies)
    {
        return Http::withOptions([
            'verify' => (bool) config('invoices.gdt.verify_ssl', true),
            'cookies' => $cookies,
        ])->withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'User-Agent' => 'Mozilla/5.0 Laravel-Invoices-GDT/1.0',
            'Origin' => rtrim((string) config('invoices.gdt.base_url'), '/'),
            'Referer' => rtrim((string) config('invoices.gdt.base_url'), '/').'/',
        ]);
    }

    private function client()
    {
        return Http::withOptions([
            'verify' => (bool) config('invoices.gdt.verify_ssl', true),
        ])->connectTimeout(min(10, (int) config('invoices.gdt.timeout', 15)))
            ->timeout((int) config('invoices.gdt.timeout', 15));
    }

    private function persistSessionCookies(CookieJar $cookies): void
    {
        Cache::put($this->sessionCacheKey(), $cookies->toArray(), now()->addMinutes(10));
    }

    private function restoreSessionCookies(): CookieJar
    {
        $jar = new CookieJar();
        $stored = Cache::get($this->sessionCacheKey(), []);

        if (! is_array($stored)) {
            return $jar;
        }

        foreach ($stored as $cookie) {
            if (! is_array($cookie)) {
                continue;
            }

            try {
                $jar->setCookie(new SetCookie($cookie));
            } catch (\Throwable) {
                // Ignore one malformed upstream cookie instead of breaking authentication.
            }
        }

        return $jar;
    }

    private function sessionCacheKey(): string
    {
        return (string) config('invoices.gdt.cache_key', 'gdt_token').':auth-session-cookies';
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('invoices.gdt.base_url'), '/').'/'.ltrim($path, '/');
    }
}
