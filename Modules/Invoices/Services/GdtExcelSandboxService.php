<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

final class GdtExcelSandboxService
{
    public function loadCaptcha(int $userId): array
    {
        $cookies = new CookieJar;
        try {
            $response = $this->client($cookies)->get($this->url('/captcha'));
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối GDT để tải captcha.', previous: $exception);
        }
        if (! $response->successful()) {
            throw new RuntimeException("GDT trả lỗi HTTP {$response->status()} khi tải captcha.");
        }
        Cache::put($this->cookieKey($userId), $cookies->toArray(), now()->addMinutes(10));
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    public function login(int $userId, string $taxCode, string $password, string $captchaKey, string $captchaValue): void
    {
        $cookies = $this->restoreCookies($userId);
        if (count($cookies->toArray()) === 0) {
            throw new RuntimeException('Phiên captcha đã hết hạn. Vui lòng tải captcha mới.');
        }

        try {
            $response = $this->client($cookies, true)
                ->withHeader('request-id', (string) Str::uuid())
                ->post($this->url('/security-taxpayer/authenticate'), [
                    'username' => $taxCode,
                    'password' => $password,
                    'ckey' => $captchaKey,
                    'cvalue' => $captchaValue,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('GDT không phản hồi khi đăng nhập.', previous: $exception);
        }

        $payload = $response->json();
        $token = is_array($payload) ? ($payload['token'] ?? $payload['accessToken'] ?? null) : null;
        if (! $response->successful() || ! is_string($token) || $token === '') {
            $message = is_array($payload) ? ($payload['message'] ?? $payload['error'] ?? null) : null;
            throw new RuntimeException(is_string($message) && trim($message) !== '' ? trim($message) : "Đăng nhập GDT không thành công (HTTP {$response->status()}).");
        }

        Cache::put($this->tokenKey($userId), ['token' => $token, 'tax_code' => $taxCode], now()->addMinutes(30));
        $this->rememberAccount($userId, $taxCode, $password);
        Cache::forget($this->cookieKey($userId));
    }

    public function savedAccounts(int $userId): array
    {
        $accounts = $this->readVault($userId);
        $taxCodes = array_keys($accounts);
        sort($taxCodes, SORT_NATURAL);

        return $taxCodes;
    }

    public function savedPassword(int $userId, string $taxCode): string
    {
        $accounts = $this->readVault($userId);
        $encrypted = $accounts[$taxCode]['password'] ?? null;
        if (! is_string($encrypted) || $encrypted === '') {
            throw new RuntimeException('Không tìm thấy thông tin đăng nhập đã lưu cho MST này.');
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Không thể giải mã thông tin đăng nhập đã lưu.', previous: $exception);
        }
    }

    public function connectedTaxCode(int $userId): ?string
    {
        $session = Cache::get($this->tokenKey($userId));

        return is_array($session) && is_string($session['tax_code'] ?? null) ? $session['tax_code'] : null;
    }

    public function disconnect(int $userId): void
    {
        Cache::forget($this->tokenKey($userId));
        Cache::forget($this->cookieKey($userId));
    }

    public function export(int $userId, string $fromDate, string $toDate, string $type): array
    {
        $session = Cache::get($this->tokenKey($userId));
        if (! is_array($session) || blank($session['token'] ?? null) || blank($session['tax_code'] ?? null)) {
            throw new RuntimeException('Phiên test GDT chưa được kết nối hoặc đã hết hạn.');
        }

        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);
        $rows = [];
        $cursor = $from->copy();

        // Keep the sandbox query contract identical to the proven /admin/invoices/hoadon
        // synchronizer: GDT is queried month-by-month, then each month is paginated by state.
        // A single multi-month query can be rejected by GDT with HTTP 400.
        while ($cursor->lte($to)) {
            $chunkStart = $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();
            $chunkEnd = $monthEnd->lt($to) ? $monthEnd : $to->copy();

            $rows = array_merge(
                $rows,
                $this->fetchMonth((string) $session['token'], $chunkStart, $chunkEnd, $type),
                $this->fetchMonth((string) $session['token'], $chunkStart, $chunkEnd, $type, '8'),
            );

            $cursor = $chunkEnd->copy()->addDay();
        }

        // Both GDT result sets may contain the same invoice. Preserve the first canonical row.
        $rows = array_values(collect($rows)->unique(fn (array $row) => implode('|', [
            $row['MST người bán'], $row['Ký hiệu mẫu số'], $row['Ký hiệu hóa đơn'],
            $row['Số hóa đơn'], $row['Ngày lập'], $row['MST người mua'],
        ]))->all());

        if ($rows === []) {
            return ['count' => 0, 'filename' => null];
        }

        $taxCode = preg_replace('/[^0-9A-Za-z_-]/', '', (string) $session['tax_code']) ?: 'unknown';
        $folder = $this->folder($userId, $taxCode);
        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException('Không thể tạo thư mục lưu file test GDT.');
        }

        $direction = $type === 'purchase' ? 'purchase' : 'sold';
        $filename = sprintf('%s_%s_%s_%s_%s.xlsx', $direction, $taxCode, $from->format('Y-m-d'), $to->format('Y-m-d'), now()->format('Ymd_His'));
        (new FastExcel($rows))->export($folder.DIRECTORY_SEPARATOR.$filename);

        return ['count' => count($rows), 'filename' => $filename];
    }

    public function files(int $userId, ?string $taxCode = null): array
    {
        $base = storage_path('app/gdt-test/'.$userId);
        if (! is_dir($base)) {
            return [];
        }

        $files = [];
        $pattern = $taxCode ? $base.'/'.$this->safeTaxCode($taxCode).'/*.xlsx' : $base.'/*/*.xlsx';
        foreach (glob($pattern) ?: [] as $path) {
            $files[] = [
                'filename' => basename($path),
                'tax_code' => basename(dirname($path)),
                'size' => filesize($path) ?: 0,
                'modified_at' => date('d/m/Y H:i', filemtime($path) ?: time()),
                'mtime' => filemtime($path) ?: 0,
            ];
        }
        usort($files, fn (array $a, array $b) => $b['mtime'] <=> $a['mtime']);

        return array_slice($files, 0, 30);
    }

    public function availableTaxCodes(int $userId): array
    {
        $base = storage_path('app/gdt-test/'.$userId);
        $taxCodes = $this->savedAccounts($userId);
        foreach (glob($base.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $taxCodes[] = basename($directory);
        }
        $taxCodes = array_values(array_unique(array_filter($taxCodes)));
        sort($taxCodes, SORT_NATURAL);

        return $taxCodes;
    }

    public function existingExport(int $userId, string $taxCode, string $fromDate, string $toDate, string $type): ?array
    {
        $taxCode = $this->safeTaxCode($taxCode);
        $direction = $type === 'purchase' ? 'purchase' : 'sold';
        $from = Carbon::parse($fromDate)->format('Y-m-d');
        $to = Carbon::parse($toDate)->format('Y-m-d');
        $prefix = sprintf('%s_%s_%s_%s_', $direction, $taxCode, $from, $to);
        $matches = glob($this->folder($userId, $taxCode).DIRECTORY_SEPARATOR.$prefix.'*.xlsx') ?: [];
        if ($matches === []) {
            return null;
        }
        usort($matches, fn (string $a, string $b) => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
        $path = $matches[0];

        return ['tax_code' => $taxCode, 'filename' => basename($path), 'modified_at' => date('d/m/Y H:i', filemtime($path) ?: time())];
    }

    public function deleteFile(int $userId, string $taxCode, string $filename): void
    {
        $path = $this->filePath($userId, $taxCode, $filename);
        if (! @unlink($path)) {
            throw new RuntimeException('Không thể xóa file Excel trên server.');
        }
    }

    public function filePath(int $userId, string $taxCode, string $filename): string
    {
        abort_unless(basename($taxCode) === $taxCode && basename($filename) === $filename, 422);
        $path = $this->folder($userId, $taxCode).DIRECTORY_SEPARATOR.$filename;
        abort_unless(is_file($path) && is_readable($path), 404);

        return $path;
    }

    private function fetchMonth(string $token, Carbon $from, Carbon $to, string $type, ?string $processingStatus = null): array
    {
        $search = "tdlap=ge={$from->format('d/m/Y')}T00:00:00;tdlap=le={$to->format('d/m/Y')}T23:59:59";
        $state = null;
        $rows = [];
        $total = null;

        do {
            $query = ['sort' => 'tdlap:desc', 'size' => 50, 'search' => $search];
            if ($processingStatus !== null) $query['search'] .= ';ttxly=='.$processingStatus;
            if ($state) $query['state'] = $state;
            $response = $this->queryClient($token)->get($this->url('/query/invoices/'.$type), $query);
            if (in_array($response->status(), [401, 403], true)) {
                throw new RuntimeException($response->status() === 401 ? 'Phiên GDT đã hết hạn.' : 'GDT từ chối truy vấn hóa đơn (HTTP 403).');
            }
            if (! $response->successful()) {
                throw new RuntimeException("GDT trả lỗi HTTP {$response->status()} khi đồng bộ.");
            }
            $data = $response->json();
            $items = is_array($data['datas'] ?? null) ? $data['datas'] : [];
            foreach ($items as $item) {
                if (is_array($item)) $rows[] = $this->mapRow($item);
            }
            $total ??= (int) ($data['total'] ?? count($items));
            $next = $data['state'] ?? null;
            $state = $next && $next !== $state ? $next : null;
        } while ($state && $items && count($rows) < $total);

        if ($total !== null && count($rows) !== $total) {
            throw new RuntimeException("GDT trả thiếu dữ liệu trong tháng {$from->format('m/Y')}: nhận ".count($rows)."/{$total} hóa đơn; không tạo file Excel thiếu.");
        }

        return $rows;
    }

    private function mapRow(array $item): array
    {
        return [
            'Ký hiệu mẫu số' => $item['khmshdon'] ?? '',
            'Ký hiệu hóa đơn' => $item['khhdon'] ?? '',
            'Số hóa đơn' => $item['shdon'] ?? '',
            'Ngày lập' => isset($item['tdlap']) ? Carbon::parse($item['tdlap'])->format('d/m/Y') : '',
            'MST người bán' => $item['nbmst'] ?? '',
            'Người bán' => $item['nbten'] ?? '',
            'MST người mua' => $item['nmmst'] ?? '',
            'Người mua' => $item['nmten'] ?? '',
            'Trước VAT' => $item['tgtcthue'] ?? 0,
            'Tiền VAT' => $item['tgtthue'] ?? 0,
            'Tổng tiền' => $item['tgtttbso'] ?? 0,
        ];
    }

    private function client(CookieJar $cookies, bool $auth = false)
    {
        $timeout = $auth ? max(30, (int) config('invoices.gdt.auth_timeout', 45)) : (int) config('invoices.gdt.timeout', 15);

        return Http::withOptions(['verify' => (bool) config('invoices.gdt.verify_ssl', true), 'cookies' => $cookies])
            ->connectTimeout(min(15, $timeout))->timeout($timeout)
            ->withHeaders($this->headers());
    }

    private function queryClient(string $token)
    {
        return Http::withOptions(['verify' => (bool) config('invoices.gdt.verify_ssl', true)])
            ->connectTimeout(10)->timeout((int) config('invoices.gdt.timeout', 15))
            ->withToken($token)->withHeaders($this->headers() + ['request-id' => (string) Str::uuid()]);
    }

    private function headers(): array
    {
        return ['Accept' => 'application/json, text/plain, */*', 'Origin' => $this->origin(), 'Referer' => $this->origin().'/', 'Action' => '', 'End-Point' => '/'];
    }

    private function restoreCookies(int $userId): CookieJar
    {
        $jar = new CookieJar;
        foreach ((array) Cache::get($this->cookieKey($userId), []) as $cookie) {
            if (! is_array($cookie)) continue;
            try { $jar->setCookie(new SetCookie($cookie)); } catch (\Throwable) {}
        }

        return $jar;
    }

    private function rememberAccount(int $userId, string $taxCode, string $password): void
    {
        $taxCode = $this->safeTaxCode($taxCode);
        $accounts = $this->readVault($userId);
        $accounts[$taxCode] = [
            'password' => Crypt::encryptString($password),
            'updated_at' => now()->toIso8601String(),
        ];
        $path = $this->vaultPath($userId);
        $folder = dirname($path);
        if (! is_dir($folder) && ! mkdir($folder, 0700, true) && ! is_dir($folder)) {
            throw new RuntimeException('Không thể tạo thư mục lưu tài khoản GDT.');
        }
        if (file_put_contents($path, json_encode($accounts, JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            throw new RuntimeException('Không thể lưu thông tin đăng nhập GDT.');
        }
        @chmod($path, 0600);
    }

    private function readVault(int $userId): array
    {
        $path = $this->vaultPath($userId);
        if (! is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function vaultPath(int $userId): string { return storage_path('app/gdt-test/'.$userId.'/accounts.json'); }
    private function safeTaxCode(string $taxCode): string
    {
        $safe = preg_replace('/[^0-9A-Za-z_-]/', '', $taxCode) ?: '';
        abort_unless($safe !== '' && $safe === $taxCode, 422);

        return $safe;
    }

    private function tokenKey(int $userId): string { return 'invoices:gdt-test:'.$userId.':token'; }
    private function cookieKey(int $userId): string { return 'invoices:gdt-test:'.$userId.':cookies'; }
    private function folder(int $userId, string $taxCode): string { return storage_path('app/gdt-test/'.$userId.'/'.$taxCode); }
    private function url(string $path): string { return rtrim((string) config('invoices.gdt.base_url'), '/').'/'.ltrim($path, '/'); }

    private function origin(): string
    {
        $base = rtrim((string) config('invoices.gdt.base_url'), '/');

        return preg_replace('#/api$#', '', $base) ?: $base;
    }
}
