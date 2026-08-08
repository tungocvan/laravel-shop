<?php
namespace Modules\Admin\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Exception;

class DbConnectionService
{
    /**
     * Thử kết nối với cấu hình mới mà không lưu vào .env
     */
    public function testConnection(array $config): array
    {
        // Tạo một connection name tạm thời
        $tempConn = 'temp_test_connection';

        // Thiết lập cấu hình động vào runtime config
        Config::set("database.connections.{$tempConn}", [
            'driver'    => $config['DB_CONNECTION'] ?? 'mysql',
            'host'      => $config['DB_HOST'],
            'port'      => $config['DB_PORT'],
            'database'  => $config['DB_DATABASE'],
            'username'  => $config['DB_USERNAME'],
            'password'  => $config['DB_PASSWORD'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);

        try {
            // Thử thực hiện kết nối
            DB::connection($tempConn)->getPdo();
            return ['success' => true, 'message' => 'Kết nối cơ sở dữ liệu thành công!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi kết nối: ' . $e->getMessage()];
        }
    }
}
