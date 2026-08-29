<?php

namespace Modules\System\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Database\DatabaseBackupCatalogService;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use ZipArchive;

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

    protected ?array $tableModuleMap = null;

    public function __construct(private readonly DatabaseBackupCatalogService $backupCatalog) {}

    public function getAllTables(string $search = '', string $module = ''): array
    {
        $search = mb_substr($search, 0, 100);
        $tables = DB::select('SHOW TABLE STATUS WHERE Name LIKE ?', ['%'.$search.'%']);
        $moduleMap = $this->getTableModuleMap();

        $result = array_map(function ($table) use ($moduleMap) {
            $tableName = $table->Name;
            $fileName = $this->backupFileName($tableName);
            $backupId = $this->backupCatalog->referenceForFileName($fileName, ['sql']);

            return [
                'name' => $tableName,
                'module' => $moduleMap[$tableName] ?? 'Unknown',
                'rows' => $table->Rows,
                'size_mb' => round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2),
                'collation' => $table->Collation,
                'has_backup' => $backupId !== null,
                'backup_file' => $fileName,
                'backup_id' => $backupId,
                'is_protected' => in_array($tableName, $this->protectedTables, true),
            ];
        }, $tables);

        if ($module !== '') {
            $result = array_values(array_filter(
                $result,
                static fn (array $table): bool => $table['module'] === $module,
            ));
        }

        return $result;
    }

    public function getModuleOptions(): array
    {
        $currentTables = array_flip($this->getCurrentTableNames());
        $modules = [];

        foreach ($this->getTableModuleMap() as $table => $module) {
            if (isset($currentTables[$table])) {
                $modules[$module] = true;
            }
        }

        foreach (array_keys($currentTables) as $table) {
            if (! isset($this->getTableModuleMap()[$table])) {
                $modules['Unknown'] = true;
                break;
            }
        }

        $options = array_keys($modules);
        natcasesort($options);

        return array_values($options);
    }

    public function backupTable(string $tableName): bool
    {
        $this->assertAllowedTable($tableName, allowProtected: true);

        $fileName = $this->backupFileName($tableName);
        $this->dumpAtomically([$tableName], "private/backups/{$fileName}", 120);

        return true;
    }

    public function backupTablesAsZip(array $tableNames): string
    {
        $tableNames = array_values(array_unique(array_filter($tableNames, 'is_string')));

        if ($tableNames === []) {
            throw new Exception('Không có bảng nào được chọn để export.');
        }

        foreach ($tableNames as $tableName) {
            $this->assertAllowedTable($tableName, allowProtected: true);
        }

        if (! class_exists(ZipArchive::class)) {
            throw new Exception('PHP Zip extension chưa được cài đặt.');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "db_tables_{$timestamp}.zip";
        $backupDirectory = Storage::disk('local')->path('private/backups');
        $tempDirectory = $backupDirectory.'/.bulk-export-'.$timestamp.'-'.bin2hex(random_bytes(4));
        $zipPath = $backupDirectory.'/'.$fileName;

        $this->ensureDirectory($backupDirectory);
        $this->ensureDirectory($tempDirectory);

        try {
            $sqlFiles = [];

            foreach ($tableNames as $tableName) {
                $sqlPath = $tempDirectory.'/'.$tableName.'.sql';
                $this->runDump([$tableName], $sqlPath, 180);
                $sqlFiles[$tableName.'.sql'] = $sqlPath;
            }

            $zip = new ZipArchive();
            $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($opened !== true) {
                throw new Exception('Không thể tạo file ZIP export.');
            }

            try {
                foreach ($sqlFiles as $entryName => $sqlPath) {
                    if (! $zip->addFile($sqlPath, $entryName)) {
                        throw new Exception("Không thể thêm {$entryName} vào file ZIP.");
                    }
                }
            } finally {
                $zip->close();
            }

            return $fileName;
        } catch (\Throwable $e) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            throw $e;
        } finally {
            $this->deleteDirectory($tempDirectory);
        }
    }

    public function backupFullDatabase(): bool
    {
        $this->createFullDatabaseBackup();

        return true;
    }

    public function createFullDatabaseBackup(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s-u');
        $fileName = "db_backup_full_{$timestamp}.sql";

        $this->dumpAtomically([], "private/backups/{$fileName}", 300);

        $backup = $this->backupCatalog->resolveTrustedFileName($fileName, ['sql']);

        if ($backup === null || ! $backup['is_full']) {
            throw new Exception('Không thể xác minh file full database backup vừa tạo.');
        }

        return array_diff_key($backup, array_flip(['relative_path', 'absolute_path']));
    }

    public function restoreTable(string $tableName): bool
    {
        $this->assertAllowedTable($tableName, allowProtected: true);

        return $this->withTableLock($tableName, function () use ($tableName): bool {
            $backup = $this->backupCatalog->resolveTrustedFileName($this->backupFileName($tableName), ['sql']);

            if ($backup === null) {
                return false;
            }

            $this->runMysqlImport($backup['absolute_path'], 300);

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
            $safetyRelativePath = 'private/backups/'.$safetyFile;
            $safetyPath = Storage::disk('local')->path($safetyRelativePath);

            $this->dumpAtomically([$tableName], $safetyRelativePath, 180);

            try {
                $this->runMysqlImport($inputPath, 300);
            } catch (\Throwable $importException) {
                try {
                    $this->runMysqlImport($safetyPath, 300);
                } catch (\Throwable $recoveryException) {
                    Log::critical('Table import and automatic recovery both failed.', [
                        'table' => $tableName,
                        'safety_backup' => $safetyFile,
                        'import_exception' => $importException::class,
                        'recovery_exception' => $recoveryException::class,
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

    public function getDownloadPath(string $backupId): ?string
    {
        return $this->backupCatalog->resolveReference($backupId, ['sql', 'zip'])['absolute_path'] ?? null;
    }

    public function getBackupDescriptor(string $backupId, array $extensions = ['sql']): ?array
    {
        $backup = $this->backupCatalog->resolveReference($backupId, $extensions);

        return $backup === null
            ? null
            : array_diff_key($backup, array_flip(['relative_path', 'absolute_path']));
    }

    public function getBackupReference(string $fileName, array $extensions = ['sql', 'zip']): ?string
    {
        return $this->backupCatalog->referenceForFileName($fileName, $extensions);
    }

    public function getTrustedBackupPath(string $fileName): ?string
    {
        return $this->backupCatalog->resolveTrustedFileName($fileName, ['sql'])['absolute_path'] ?? null;
    }

    public function getAllBackupFiles(): array
    {
        return $this->backupCatalog->listBackups(2000);
    }

    public function restoreFromFile(string $backupId): bool
    {
        $path = $this->getDownloadPath($backupId);

        if ($path === null || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'sql') {
            throw new Exception('Backup file not found.');
        }

        if (! $this->backupCatalog->isFullDatabaseBackup($path)) {
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

        $safetyRelativePath = 'private/backups/db_backup_before_restore_'.now()->format('Y-m-d_H-i-s').'.sql';
        $safetyPath = Storage::disk('local')->path($safetyRelativePath);

        try {
            $this->dumpAtomically([], $safetyRelativePath, 300);

            try {
                $this->runMysqlImport($path, 600);
            } catch (\Throwable $restoreException) {
                try {
                    $this->runMysqlImport($safetyPath, 600);
                } catch (\Throwable $recoveryException) {
                    Log::critical('Database restore and automatic recovery both failed.', [
                        'backup' => $backupId,
                        'safety_backup' => basename($safetyPath),
                        'restore_exception' => $restoreException::class,
                        'recovery_exception' => $recoveryException::class,
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

        if (! $this->backupCatalog->isFullDatabaseBackup($sourcePath)) {
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
        return $this->backupCatalog->deleteReference($backupId);
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

        $output = fopen($outputPath, 'wb');

        if ($output === false) {
            throw new Exception('Không thể tạo file database dump.');
        }

        try {
            $process = new Process($command, null, $this->processEnvironment($config));
            $process->setTimeout($timeout);
            $process->run(function (string $type, string $buffer) use ($output): void {
                if ($type === Process::OUT) {
                    fwrite($output, $buffer);
                }
            });

            if (! $process->isSuccessful()) {
                Log::error('Database dump failed.', [
                    'exit_code' => $process->getExitCode(),
                ]);

                throw new ProcessFailedException($process);
            }
        } finally {
            fclose($output);
        }
    }

    private function dumpAtomically(array $tables, string $relativePath, int $timeout): void
    {
        $finalPath = Storage::disk('local')->path($relativePath);
        $temporaryPath = $finalPath.'.partial-'.bin2hex(random_bytes(6));

        $this->ensureDirectory(dirname($finalPath));

        try {
            $this->runDump($tables, $temporaryPath, $timeout);

            if (! rename($temporaryPath, $finalPath)) {
                throw new Exception('Không thể hoàn tất file database backup.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
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

    private function getTableModuleMap(): array
    {
        if ($this->tableModuleMap !== null) {
            return $this->tableModuleMap;
        }

        $map = [];

        foreach (glob(base_path('Modules/*/database/migrations/*.php')) ?: [] as $path) {
            $relative = str_replace('\\', '/', $path);

            if (! preg_match('#/Modules/([^/]+)/database/migrations/#', $relative, $moduleMatch)) {
                continue;
            }

            foreach ($this->extractCreatedTables($path) as $table) {
                $map[$table] ??= $moduleMatch[1];
            }
        }

        foreach (glob(database_path('migrations/*.php')) ?: [] as $path) {
            foreach ($this->extractCreatedTables($path) as $table) {
                $map[$table] ??= 'Core';
            }
        }

        return $this->tableModuleMap = $map;
    }

    private function extractCreatedTables(string $migrationPath): array
    {
        $content = @file_get_contents($migrationPath);

        if (! is_string($content) || $content === '') {
            return [];
        }

        preg_match_all('/Schema::create\s*\(\s*[\'\"]([A-Za-z0-9_]+)[\'\"]/', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
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
