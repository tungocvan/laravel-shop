<?php
namespace Modules\Admin\Services\Env;

use Illuminate\Support\Facades\Http;
use Exception;
use Modules\Admin\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Cache;

class SystemConfigService
{
    /**
     * Kiểm tra xem NodeJS Server có đang hoạt động không
     */

    public function pingNodeJS(string $url, string $secret): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'x-bridge-secret' => $secret,
                    'Accept' => 'application/json'
                ])
                ->get(trim($url, '/') . '/health');

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Kết nối thành công!'];
            }

            // Log lỗi cụ thể từ response
            return ['success' => false, 'message' => 'NodeJS trả về: ' . $response->body()];
        } catch (\Exception $e) {
            // Log lỗi kết nối vật lý (Connection Refused, DNS, v.v.)
            return ['success' => false, 'message' => 'Lỗi kết nối vật lý: ' . $e->getMessage()];
        }
    }
    public function dispatchTestJob(): void
    {
        Cache::forget('queue_test_status');

        // Gọi Full Namespace để tránh lỗi Class Loading
        \Modules\Admin\Jobs\TestQueueJob::dispatch();

        // Nếu vẫn không chạy đến đây, hãy kiểm tra file composer.json
         //die('Đã gọi thành công');
    }

    public function checkQueueStatus(): string
    {
        return Cache::get('queue_test_status', 'Pending...');
    }
}
