<?php

namespace Modules\System\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DatabaseService
{
    protected array $protectedTables = [
        'users',
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'roles',
        'permissions',
    ];

    public function getAllTables(string $search = ''): array
    {
        $search = mb_substr($search, 0, 100);
        $tables = DB::select('SHOW TABLE STATUS WHERE Name LIKE ?', ['%'.$search.'%']);

        return array_map(function ($table) {
            $tableName = $table->Name;
            $fileName = $this->backupFileName($tableName);

            return [
                'name' => $tableName,
                'rows' => $table->Rows,
                'size_mb' => round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2),
                'collation' => $table->Collation,
                'has_backup' => Storage::disk('local')->exists("private/backups/{$fileName}"),
                'backup_file' => $fileName,
                'is_protected' => in_array($tableName, $this->protectedTables, true),
            ];
        }, $tables);
    }

    public function backupTable(string $tableName): bool
    {
        $this->assertAllowedTable($tableName, allowProtected: true);

        $fileName = $this->backupFileName($tableName);
        $path = Storage::disk('local')->path("private/backups/{$fileName}");

        $this->ensureDirectory(dirname($path));
        $this->runDump([$tableName], $path, 120);

        return true;
    }

    public function backupTables(array $tableNames): string
    {
        $tableNames = array_values(array_unique(array_filter($tableNames, 'is_string')));

        if ($tableNames === []) {
            throw new Exception('Không có bảng nào được chọn để export.');
        }

        foreach ($tableNames as $tableName) {
            $this->assertAllowedTable($tableName, allowProtected: true);
        }

        $fileName = 'backup_selected_'.now()->format('Y-m-d_H-i-s').'.sql';
        $path = Storage::disk('local')->path('private/backups/'.$fileName);

        $this->ensureDirectory(dirname($path));
        $this->runDump($tableNames, $path, 300);

        return $fileName;
    }

    public function backupFullDatabase(): bool
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "db_backup_full_{$timestamp}.sql";
        $path = Storage::disk('local')->path("private/backups/{$fileName}");

        $this->ensureDirectory(dirname($path));
        $this->runDump([], $path, 300);

        return true;
    }

    public function restoreTable(string $tableName): bool
    {
        $this->assertAllowedTable($tableName, allowProtected: true);

        return $this->withTableLock($tableName, function () use ($tableName): bool {
            $path = Storage::disk('local')->path('private/backups/'.$this->backupFileName($tableName));

            if (! file_exists($path)) {
                return false;
            }

            $this->runMysqlImport($path, 300);

            return true;
        });
    }

    public function importTableFromFile(string $tableName, string $inputPath): string
    {
        $this->assertAllowedTable($tableName);
        $this->assertReadableSqlFile($inputPath);
        $this->assertSingleTableDumpForTarget($inputPath, $tableName);

        return $this->withTableLock($tableName, function () use ($tableName, $inputPath): string {
            $safetyFile = 'backup_'.$tableName.'_before_import_'.now()->format('Y-m-d_H-i-s').'.sql';
            $safetyPath = Storage::disk('local')->path('private/backups/'.$safetyFile);

            $this->ensureDirectory(dirname($safetyPath));
            $this->runDump([$tableName], $safetyPath, 180);

            try {
                $this->runMysqlImport($inputPath, 300);
            } catch (\Throwable $importException) {
                try {
                    $this->runMysqlImport($safetyPath, 300);
                } catch (\Throwable $recoveryException) {
                    Log::critical('Table import and automatic recovery both failed.', [
                        'table' => $tableName,
                        'safety_backup' => $safetyFile,
                        'import_error' => $importException->getMessage(),
                        'recovery_error' => $recoveryException->getMessage(),
                    ]);

                    throw new Exception(
                        'Import bảng thất bại và không thể tự phục hồi. Vui lòng kiểm tra log hệ thống.',
                        previous: $importException,
                    );
                }

                throw new Exception(
                    'Import bảng thất bại. Dữ liệu cũ đã được phục hồi.',
                    previous: $importException,
                );
            }

            DB::purge();
            DB::reconnect();

            Log::notice('Database table imported.', [
                'table' => $tableName,
                'safety_backup' => $safetyFile,
            ]);

            return $safetyFile;
        });
    }

    public function truncateTable(string $tableName): void
    {
        $this->assertAllowedTable($tableName);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table($tableName)->truncate();
            DB::statement('ANALYZE TABLE '.$this->quoteIdentifier($tableName));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function dropTable(string $tableName): void
    {
        $this->assertAllowedTable($tableName);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('DROP TABLE IF EXISTS '.$this->quoteIdentifier($tableName));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function getDownloadPath(string $fileName): ?string
    {
        return $this->resolveBackupIdentifier($fileName);
    }

    public function getAllBackupFiles(): array
    {
        $files = [];

        foreach (['private/backups', 'backups'] as $directory) {
            foreach (Storage::disk('local')->files($directory) as $path) {
                $fileName = basename($path);

                if (! preg_match('/\A[A-Za-z0-9_.-]+\.sql\z/', $fileName)) {
                    continue;
                }

                $files[] = [
                    'id' => $fileName,
                    'name' => $fileName,
                    'path' => $fileName,
                    'size' => Storage::disk('local')->size($path),
                    'time' => Storage::disk('local')->lastModified($path),
                    'is_full' => $this->looksLikeFullBackup(Storage::disk('local')->path($path)),
                ];
            }
        }

        usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $files;
    }

    public function restoreFromFile(string $backupId): bool
    {
        $path = $this->resolveBackupIdentifier($backupId);

        if ($path === null) {
            throw new Exception('Backup file not found.');
        }

        if (! $this->looksLikeFullBackup($path)) {
            throw new Exception('File đã chọn không phải full database backup hợp lệ.');
        }

        $lockPath = storage_path('framework/database-restore.lock');
        $lock = fopen($lockPath, 'c');

        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new Exception('Một tiến trình restore database khác đang chạy.');
        }

        $safetyPath = Storage::disk('local')->path(
            'private/backups/db_backup_before_restore_'.now()->format('Y-m-d_H-i-s').'.sql'
        );

        try {
            $this->ensureDirectory(dirname($safetyPath));
            $this->runDump([], $safetyPath, 300);

            try {
                $this->runMysqlImport($path, 600);
            } catch (\Throwable $restoreException) {
                try {
                    $this->runMysqlImport($safetyPath, 600);
                } catch (\Throwable $recoveryException) {
                    Log::critical('Database restore and automatic recovery both failed.', [
                        'backup' => $backupId,
                        'safety_backup' => $safetyPath,
                        'restore_error' => $restoreException->getMessage(),
                        'recovery_error' => $recoveryException->getMessage(),
                    ]);

                    throw new Exception(
                        'Restore thất bại và không thể tự phục hồi. Bản an toàn nằm tại: '.basename($safetyPath),
                        previous: $restoreException,
                    );
                }

                throw new Exception(
                    'Restore thất bại. Hệ thống đã tự phục hồi database về trạng thái trước khi restore.',
                    previous: $restoreException,
                );
            }

            DB::purge();
            DB::reconnect();

            Log::notice('Full database restored.', [
                'backup' => $backupId,
                'safety_backup' => basename($safetyPath),
            ]);

            return true;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function importBackupFile(string $sourcePath, string $originalName): string
    {
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'sql') {
            throw new Exception('Chỉ chấp nhận file có phần mở rộng .sql.');
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new Exception('Không thể đọc file SQL đã chọn.');
        }

        $size = filesize($sourcePath);
        if ($size === false || $size > 500 * 1024 * 1024) {
            throw new Exception('File SQL không được vượt quá 500 MB.');
        }

        if (! $this->looksLikeFullBackup($sourcePath)) {
            throw new Exception('File không phải full database backup MySQL/MariaDB hợp lệ.');
        }

        $safeBase = preg_replace('/[^A-Za-z0-9_.-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '.-') ?: 'database';
        $fileName = 'uploaded_'.now()->format('Y-m-d_H-i-s').'_'.substr($safeBase, 0, 80).'.sql';
        $destination = Storage::disk('local')->path('private/backups/'.$fileName);

        $this->ensureDirectory(dirname($destination));
        if (! copy($sourcePath, $destination)) {
            throw new Exception('Không thể lưu file SQL vào thư mục backup.');
        }

        return $fileName;
    }

    public function deleteBackup(string $backupId): int
    {
        if ($backupId !== basename($backupId) || ! preg_match('/\A[A-Za-z0-9_.-]+\.sql\z/', $backupId)) {
            throw new Exception('Invalid backup file.');
        }

        $deleted = 0;

        foreach (['private/backups', 'backups'] as $directory) {
            $path = "{$directory}/{$backupId}";

            if (Storage::disk('local')->exists($path) && Storage::disk('local')->delete($path)) {
                $deleted++;
            }
        }

        if ($deleted === 0) {
            throw new Exception('Backup file not found.');
        }

        return $deleted;
    }

    public function assertAllowedTable(string $tableName, bool $allowProtected = false): void
    {
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $tableName)) {
            throw new Exception('Invalid table identifier.');
        }

        if (! in_array($tableName, $this->getCurrentTableNames(), true)) {
            throw new Exception('Table does not exist.');
        }

        if (! $allowProtected && in_array($tableName, $this->protectedTables, true)) {
            throw new Exception('This table is protected.');
        }
    }

    private function runDump(array $tables, string $outputPath, int $timeout): void
    {
        $config = config('database.connections.mysql');
        $command = [
            'mysqldump',
            '--user='.($config['username'] ?? ''),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            $config['database'] ?? '',
            ...$tables,
        ];

        $process = new Process($command, null, $this->processEnvironment($config));
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Database dump failed.', [
                'exit_code' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new ProcessFailedException($process);
        }

        file_put_contents($outputPath, $process->getOutput());
    }

    private function runMysqlImport(string $inputPath, int $timeout): void
    {
        $config = config('database.connections.mysql');
        $command = [
            'mysql',
            '--user='.($config['username'] ?? ''),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            $config['database'] ?? '',
        ];

        $input = fopen($inputPath, 'rb');

        if ($input === false) {
            throw new Exception('Không thể mở file SQL để import.');
        }

        try {
            $process = new Process($command, null, $this->processEnvironment($config));
            $process->setInput($input);
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('Database import failed.', [
                    'exit_code' => $process->getExitCode(),
                    'error' => $process->getErrorOutput(),
                ]);

                throw new ProcessFailedException($process);
            }
        } finally {
            fclose($input);
        }
    }

    private function processEnvironment(array $config): array
    {
        return filled($config['password'] ?? null)
            ? ['MYSQL_PWD' => $config['password']]
            : [];
    }

    private function resolveBackupIdentifier(string $backupId): ?string
    {
        if ($backupId !== basename($backupId) || ! preg_match('/\A[A-Za-z0-9_.-]+\.sql\z/', $backupId)) {
            return null;
        }

        foreach (['private/backups', 'backups'] as $directory) {
            $path = "{$directory}/{$backupId}";

            if (Storage::disk('local')->exists($path)) {
                return Storage::disk('local')->path($path);
            }
        }

        return null;
    }

    private function looksLikeFullBackup(string $path): bool
    {
        if (! is_readable($path) || filesize($path) < 100) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $sample = fread($handle, 1024 * 1024);
        } finally {
            fclose($handle);
        }

        return is_string($sample)
            && (str_contains($sample, 'MySQL dump') || str_contains($sample, 'MariaDB dump'))
            && str_contains($sample, 'DROP TABLE IF EXISTS')
            && substr_count($sample, 'CREATE TABLE') >= 2;
    }

    private function assertReadableSqlFile(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new Exception('Không thể đọc file SQL đã chọn.');
        }

        $size = filesize($path);

        if ($size === false || $size < 20 || $size > 100 * 1024 * 1024) {
            throw new Exception('File SQL không hợp lệ hoặc vượt quá 100 MB.');
        }
    }

    private function assertSingleTableDumpForTarget(string $path, string $targetTable): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new Exception('Không thể kiểm tra file SQL.');
        }

        $tables = [];
        $hasTargetStatement = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if (preg_match_all('/\b(?:DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE|INSERT\s+INTO|LOCK\s+TABLES|ALTER\s+TABLE)\s+`?([A-Za-z0-9_]+)`?/i', $line, $matches)) {
                    foreach ($matches[1] as $table) {
                        $tables[$table] = true;
                        if ($table === $targetTable) {
                            $hasTargetStatement = true;
                        }
                    }
                }

                if (count($tables) > 1) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        if (! $hasTargetStatement || array_keys($tables) !== [$targetTable]) {
            throw new Exception('File SQL không hợp lệ cho bảng đã chọn.');
        }
    }

    private function withTableLock(string $tableName, callable $callback): mixed
    {
        $lockPath = storage_path('framework/database-table-'.$tableName.'.lock');
        $lock = fopen($lockPath, 'c');

        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new Exception('Bảng đang được xử lý bởi một tiến trình khác.');
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function backupFileName(string $tableName): string
    {
        return "backup_{$tableName}.sql";
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function getCurrentTableNames(): array
    {
        return array_map(function (object $table): string {
            $values = get_object_vars($table);

            return (string) reset($values);
        }, DB::select('SHOW TABLES'));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}
