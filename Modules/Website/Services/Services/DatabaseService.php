<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exception;
use Illuminate\Support\Str;


class DatabaseService
{
    // Danh sách bảng cấm xóa/truncate để bảo vệ hệ thống
    protected array $protectedTables = [
        'users', 'migrations', 'failed_jobs', 'password_reset_tokens', 'roles', 'permissions'
    ];

    /**
     * Lấy danh sách bảng và metadata
     */
    public function getAllTables(string $search = ''): array
    {
        // Sử dụng query trần để lấy metadata chính xác từ MySQL
        $query = "SHOW TABLE STATUS WHERE Name LIKE ?";
        $tables = DB::select($query, ['%' . $search . '%']);

        return array_map(function ($table) {
            $tableName = $table->Name;
            $fileName = "backup_{$tableName}.sql";

            return [
                'name' => $tableName,
                'rows' => $table->Rows,
                'size_mb' => round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2),
                'collation' => $table->Collation,
                'has_backup' => Storage::disk('local')->exists("backups/{$fileName}"),
                'backup_file' => $fileName,
                'is_protected' => in_array($tableName, $this->protectedTables),
            ];
        }, $tables);
    }

    /**
     * Backup một bảng cụ thể (Quick Export)
     */
   /**
 * Backup một bảng cụ thể (Quick Export)
 */
public function backupTable(string $tableName): bool
{
    $fileName = "backup_{$tableName}.sql";
    $path = storage_path("app/private/backups/{$fileName}");

    // Đảm bảo thư mục tồn tại
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

    // Lấy config DB
    $config = config('database.connections.mysql');

    // Xây dựng lệnh mysqldump
    // Lưu ý: Dùng sprintf để code sạch hơn và dễ debug
    $command = sprintf(
        'mysqldump --user="%s" --password="%s" --host="%s" --port="%s" "%s" "%s" > "%s"',
        $config['username'],
        $config['password'],
        $config['host'],
        $config['port'] ?? '3306',
        $config['database'],
        $tableName, // Thêm tên bảng vào lệnh
        $path
    );

    try {
        // 1. Khởi tạo Process từ dòng lệnh Shell (để hiểu ký tự >)
        $process = \Symfony\Component\Process\Process::fromShellCommandline($command);

        // 2. ⚠️ SỬA LỖI TẠI ĐÂY: Dùng setTimeout thay vì timeout
        $process->setTimeout(120);

        // 3. Chạy lệnh
        $process->run();

        // 4. Kiểm tra thành công
        if (!$process->isSuccessful()) {
            throw new \Symfony\Component\Process\Exception\ProcessFailedException($process);
        }

        return true;

    } catch (\Exception $e) {
        \Log::error("Export Table {$tableName} Failed: " . $e->getMessage());
        throw new \Exception("Lỗi Export bảng {$tableName}: " . $e->getMessage());
    }
}
    /**
     * Backup toàn bộ DB
     */
    public function backupFullDatabase(): bool
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "db_backup_full_{$timestamp}.sql";
        $path = storage_path("app/backups/{$fileName}");

        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

        $config = config('database.connections.mysql');

        // Command mysqldump chuẩn
        $command = sprintf(
            'mysqldump --user="%s" --password="%s" --host="%s" --port="%s" "%s" > "%s"',
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port'] ?? '3306',
            $config['database'],
            $path
        );

        try {
            // ✅ SỬ DỤNG SYMFONY PROCESS TRỰC TIẾP
            // Hàm fromShellCommandline() chỉ có ở đây
            $process = Process::fromShellCommandline($command);

            // Set timeout cao (ví dụ 5 phút) để tránh lỗi với DB lớn
            $process->setTimeout(300);

            $process->run();

            // Kiểm tra kết quả
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return true;

        } catch (Exception $e) {
            \Log::error("Backup Failed: " . $e->getMessage());
            // Ném lỗi ra để Livewire hiển thị
            throw new Exception("Lỗi Export: " . $e->getMessage());
        }
    }

    /**
     * Restore dữ liệu từ file backup
     */
    /**
 * Restore dữ liệu từ file backup
 */
public function restoreTable(string $tableName): bool
{
    $fileName = "backup_{$tableName}.sql";
    $path = storage_path("app/private/backups/{$fileName}");

    if (!file_exists($path)) return false;

    $config = config('database.connections.mysql');

    // Xây dựng lệnh mysql import (dùng ký tự < )
    $command = sprintf(
        'mysql --user="%s" --password="%s" --host="%s" --port="%s" "%s" < "%s"',
        $config['username'],
        $config['password'],
        $config['host'],
        $config['port'] ?? '3306',
        $config['database'],
        $path
    );

    try {
        // 1. Tạo process từ Shell Command
        $process = \Symfony\Component\Process\Process::fromShellCommandline($command);

        // 2. ⚠️ SỬA LỖI TẠI ĐÂY: Dùng setTimeout() thay vì timeout()
        // Restore thường tốn thời gian hơn backup, nên set 300s (5 phút)
        $process->setTimeout(300);

        // 3. Chạy lệnh
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Symfony\Component\Process\Exception\ProcessFailedException($process);
        }

        return true;

    } catch (\Exception $e) {
        \Log::error("Restore Table {$tableName} Failed: " . $e->getMessage());
        throw new \Exception("Lỗi Restore bảng {$tableName}: " . $e->getMessage());
    }
}


    /**
     * Làm sạch dữ liệu (Truncate) an toàn
     */
    /**
 * Làm sạch dữ liệu (Truncate) an toàn và cập nhật lại Index
 */
public function truncateTable(string $tableName): void
{
    if (in_array($tableName, $this->protectedTables)) {
        throw new Exception("Bảng {$tableName} được bảo vệ, không thể làm sạch.");
    }

    DB::transaction(function () use ($tableName) {
        // 1. Tắt check khóa ngoại để tránh lỗi ràng buộc
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Xóa sạch dữ liệu
        DB::table($tableName)->truncate();

        // 3. 🟢 QUAN TRỌNG: Ép MySQL tính toán lại thống kê bảng ngay lập tức
        // Lệnh này giúp 'Rows' trong SHOW TABLE STATUS cập nhật về 0
        DB::statement("ANALYZE TABLE `{$tableName}`");

        // 4. Bật lại check khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    });
}

    /**
     * Xóa bảng (Drop) - Nguy hiểm
     */
    public function dropTable(string $tableName): void
    {
        if (in_array($tableName, $this->protectedTables)) {
            throw new Exception("Bảng {$tableName} được bảo vệ, không thể xóa.");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Lấy đường dẫn file download (để Controller dùng)
     */
    public function getDownloadPath(string $fileName): ?string
    {
        // Validate filename để tránh Path Traversal attack
        if (str_contains($fileName, '..') || !str_ends_with($fileName, '.sql')) {
            return null;
        }

        $path = "backups/{$fileName}";
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }
        return null;
    }

    public function getAllBackupFiles(): array
    {
        $paths = [
            storage_path('app/backups'),
            storage_path('app/private/backups'),
        ];

        $files = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) continue;

            foreach (glob($path . '/*.sql') as $file) {
                $files[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'time' => filemtime($file),
                ];
            }
        }

        // sort newest first
        usort($files, fn($a, $b) => $b['time'] <=> $a['time']);

        return $files;
    }



    public function restoreFromFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Backup file not found');
        }

        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        try {
            // ⚠️ Disable FK
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 🔥 Drop all tables
            $tables = \DB::select('SHOW TABLES');
            $key = 'Tables_in_' . $db;

            foreach ($tables as $table) {
                \DB::statement("DROP TABLE IF EXISTS `{$table->$key}`");
            }

            // ⚠️ Enable FK lại
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // 🚀 Import bằng CLI (FAST + SAFE)
            $command = [
                'mysql',
                "-h{$host}",
                "-u{$user}",
            ];

            if ($pass) {
                $command[] = "-p{$pass}";
            }

            $command[] = $db;

            $process = new Process($command);
            $process->setInput(file_get_contents($filePath));
            $process->setTimeout(0);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            return true;

        } catch (\Throwable $e) {
            \Log::error('Restore DB failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
