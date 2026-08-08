<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class FacebookTestCommand extends Command
{
    /**
     * Tên câu lệnh Artisan.
     *
     * Có thể thêm --show-token để hiển thị App Access Token.
     * Chỉ nên dùng trong môi trường phát triển.
     */
    protected $signature = 'facebook:test
                            {--show-token : Hiển thị App Access Token đầy đủ}';

    /**
     * Mô tả câu lệnh.
     */
    protected $description = 'Kiểm tra Facebook App ID, App Secret và kết nối Meta Graph API';

    /**
     * Thực thi command.
     */
    public function handle(): int
    {
        $graphVersion = trim((string) config('facebook.config.graph_version', 'v25.0'));
        $appId = trim((string) config('facebook.config.app_id'));
        $appSecret = trim((string) config('facebook.config.app_secret'));

        $this->newLine();
        $this->info('Facebook Graph API Connection Test');
        $this->line(str_repeat('-', 45));

        /*
         * Bước 1: Kiểm tra cấu hình.
         */
        if ($appId === '') {
            $this->error('Thiếu FACEBOOK_APP_ID trong file .env.');

            return self::FAILURE;
        }

        if ($appSecret === '') {
            $this->error('Thiếu FACEBOOK_APP_SECRET trong file .env.');

            return self::FAILURE;
        }

        if (!preg_match('/^v\d+\.\d+$/', $graphVersion)) {
            $this->error(
                "FACEBOOK_GRAPH_VERSION không hợp lệ: {$graphVersion}"
            );
            $this->line('Ví dụ hợp lệ: v25.0');

            return self::FAILURE;
        }

        $this->table(
            ['Cấu hình', 'Giá trị'],
            [
                ['Graph version', $graphVersion],
                ['App ID', $this->mask($appId, 4, 4)],
                ['App Secret', $this->mask($appSecret, 2, 4)],
            ]
        );

        /*
         * Bước 2: Lấy App Access Token.
         */
        $tokenUrl = sprintf(
            'https://graph.facebook.com/%s/oauth/access_token',
            $graphVersion
        );

        $this->line('Đang kiểm tra kết nối tới Meta Graph API...');

        try {
            $tokenResponse = Http::acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 500)
                ->get($tokenUrl, [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException $exception) {
            $this->error('Không thể kết nối tới graph.facebook.com.');
            $this->line($exception->getMessage());

            $this->showLinuxChecks();

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Có lỗi xảy ra khi gọi Meta Graph API.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        if ($tokenResponse->failed()) {
            $this->showFacebookError(
                'Không lấy được App Access Token',
                $tokenResponse->status(),
                $tokenResponse->json()
            );

            return self::FAILURE;
        }

        $appAccessToken = $tokenResponse->json('access_token');

        if (!is_string($appAccessToken) || trim($appAccessToken) === '') {
            $this->error('Meta không trả về access_token hợp lệ.');
            $this->line('Response:');
            $this->line($tokenResponse->body());

            return self::FAILURE;
        }

        $this->info('✓ Kết nối Meta Graph API thành công.');
        $this->info('✓ FACEBOOK_APP_ID và FACEBOOK_APP_SECRET hợp lệ.');

        if ($this->option('show-token')) {
            if (app()->environment('production')) {
                $this->warn(
                    'Không hiển thị token vì ứng dụng đang ở môi trường production.'
                );
            } else {
                $this->warn('App Access Token:');
                $this->line($appAccessToken);
                $this->newLine();
                $this->warn(
                    'Không chia sẻ hoặc lưu token này trong Git/log công khai.'
                );
            }
        } else {
            $this->line(
                'App Access Token: '.$this->mask($appAccessToken, 8, 6)
            );
        }

        /*
         * Bước 3: Dùng App Access Token đọc thông tin ứng dụng.
         */
        $appInfoUrl = sprintf(
            'https://graph.facebook.com/%s/%s',
            $graphVersion,
            urlencode($appId)
        );

        $this->newLine();
        $this->line('Đang xác minh thông tin Facebook App...');

        try {
            $appResponse = Http::acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 500)
                ->get($appInfoUrl, [
                    'fields' => 'id,name,link',
                    'access_token' => $appAccessToken,
                ]);
        } catch (ConnectionException $exception) {
            $this->error('Có token nhưng không thể đọc thông tin ứng dụng.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Có lỗi khi xác minh Facebook App.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        if ($appResponse->failed()) {
            $this->showFacebookError(
                'Đã lấy được token nhưng không đọc được thông tin App',
                $appResponse->status(),
                $appResponse->json()
            );

            return self::FAILURE;
        }

        $appData = $appResponse->json();

        $this->table(
            ['Thông tin ứng dụng', 'Giá trị'],
            [
                ['App ID', $appData['id'] ?? $appId],
                ['App name', $appData['name'] ?? '(Meta không trả về)'],
                ['App link', $appData['link'] ?? '(Không có)'],
            ]
        );

        $this->newLine();
        $this->info('✓ Facebook App hoạt động bình thường.');

        $this->newLine();
        $this->warn(
            'Lưu ý: App Access Token không dùng để đăng bài lên Fanpage.'
        );
        $this->line(
            'Để đăng bài, bạn vẫn cần User Access Token và Page Access Token.'
        );

        return self::SUCCESS;
    }

    /**
     * Che thông tin nhạy cảm khi hiển thị trên terminal.
     */
    private function mask(
        string $value,
        int $visibleStart = 4,
        int $visibleEnd = 4
    ): string {
        $length = strlen($value);

        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat('*', max($length, 8));
        }

        return substr($value, 0, $visibleStart)
            .str_repeat('*', $length - $visibleStart - $visibleEnd)
            .substr($value, -$visibleEnd);
    }

    /**
     * Hiển thị lỗi chuẩn của Facebook Graph API.
     *
     * @param array<string, mixed>|null $response
     */
    private function showFacebookError(
        string $title,
        int $httpStatus,
        ?array $response
    ): void {
        $error = $response['error'] ?? [];

        $this->newLine();
        $this->error($title);
        $this->table(
            ['Thuộc tính', 'Giá trị'],
            [
                ['HTTP status', (string) $httpStatus],
                ['Error type', $error['type'] ?? '(Không xác định)'],
                ['Error code', isset($error['code'])
                    ? (string) $error['code']
                    : '(Không xác định)'],
                ['Error subcode', isset($error['error_subcode'])
                    ? (string) $error['error_subcode']
                    : '(Không có)'],
                ['Message', $error['message'] ?? 'Không có thông báo lỗi'],
                ['Trace ID', $error['fbtrace_id'] ?? '(Không có)'],
            ]
        );
    }

    /**
     * Gợi ý kiểm tra kết nối trên Linux.
     */
    private function showLinuxChecks(): void
    {
        $this->newLine();
        $this->warn('Bạn có thể kiểm tra Linux bằng các lệnh:');
        $this->line('curl -I https://graph.facebook.com');
        $this->line('getent hosts graph.facebook.com');
        $this->line('php -m | grep -i openssl');
        $this->line('php -m | grep -i curl');
    }
}
