<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\DatabaseService;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use ZipArchive;

class ModuleSnapshotService
{
    private const FORMAT_VERSION = '1.0';

    private const MAX_SCAN_FILES = 500;

    private const MAX_PACKAGE_BYTES = 1024 * 1024 * 1024;

    public function __construct(private readonly DatabaseService $database) {}

    public function tablesForModule(string $module): array
    {
        $this->assertModuleName($module);

        if ($module === 'Unknown' || ! in_array($module, $this->database->getModuleOptions(), true)) {
            throw new RuntimeException('Module không hợp lệ hoặc chưa có ownership mapping rõ ràng.');
        }

        $tables = array_values(array_unique(array_column($this->database->getAllTables('', $module), 'name')));
        sort($tables, SORT_STRING);

        if ($tables === []) {
            throw new RuntimeException('Module không có bảng dữ liệu để tạo snapshot.');
        }

        foreach ($tables as $table) {
            $this->database->assertAllowedTable($table, allowProtected: true);
        }

        return $tables;
    }

    public function create(string $module, string $snapshotType = 'manual'): array
    {
        $tables = $this->tablesForModule($module);
        $timestamp = now();
        $relativeDirectory = sprintf(
            'private/backups/modules/%s/%s/%s',
            $module,
            $timestamp->format('Y'),
            $timestamp->format('m'),
        );
        $prefix = $snapshotType === 'safety' ? 'safety' : 'snapshot';
        $fileName = sprintf(
            '%s_%s_%s.zip',
            $prefix,
            strtolower($module),
            $timestamp->format('Y-m-d_H-i-s-u'),
        );
        $relativePath = $relativeDirectory.'/'.$fileName;
        $absolutePath = Storage::disk('local')->path($relativePath);
        $temporaryDirectory = storage_path('framework/module-snapshots/'.bin2hex(random_bytes(10)));
        $sqlPath = $temporaryDirectory.'/module.sql';
        $manifestPath = $temporaryDirectory.'/manifest.json';
        $checksumsPath = $temporaryDirectory.'/checksums.json';

        $this->ensureDirectory($temporaryDirectory);
        $this->ensureDirectory(dirname($absolutePath));

        try {
            $this->runDump($tables, $sqlPath, 600);
            $checksum = hash_file('sha256', $sqlPath);

            if (! is_string($checksum) || $checksum === '') {
                throw new RuntimeException('Không thể tính checksum cho module snapshot.');
            }

            $manifest = [
                'format_version' => self::FORMAT_VERSION,
                'snapshot_type' => $snapshotType,
                'module' => $module,
                'created_at' => $timestamp->toIso8601String(),
                'database_driver' => (string) config('database.default'),
                'database_name' => (string) config('database.connections.mysql.database'),
                'tables' => $tables,
                'row_counts' => $this->rowCounts($module),
                'schema_fingerprint' => $this->schemaFingerprint($tables),
                'app_commit' => trim((string) env('APP_COMMIT', '')) ?: null,
            ];
            $checksums = ['module.sql' => $checksum];

            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            file_put_contents($checksumsPath, json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $partial = $absolutePath.'.partial-'.bin2hex(random_bytes(6));
            $zip = new ZipArchive;
            $opened = $zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($opened !== true) {
                throw new RuntimeException('Không thể tạo package module snapshot.');
            }

            try {
                foreach ([
                    'manifest.json' => $manifestPath,
                    'module.sql' => $sqlPath,
                    'checksums.json' => $checksumsPath,
                ] as $entry => $path) {
                    if (! $zip->addFile($path, $entry)) {
                        throw new RuntimeException('Không thể đóng gói module snapshot.');
                    }
                }
            } finally {
                $zip->close();
            }

            if (! rename($partial, $absolutePath)) {
                @unlink($partial);
                throw new RuntimeException('Không thể hoàn tất module snapshot.');
            }

            Log::notice('Module snapshot created.', [
                'module' => $module,
                'snapshot_type' => $snapshotType,
                'file' => $fileName,
                'tables' => count($tables),
            ]);

            return $this->descriptor($relativePath, $absolutePath, $manifest);
        } finally {
            $this->deleteDirectory($temporaryDirectory);
        }
    }

    public function listLocal(string $module, int $limit = 50): array
    {
        $this->tablesForModule($module);
        $files = [];
        $base = 'private/backups/modules/'.$module;

        foreach (Storage::disk('local')->allFiles($base) as $relativePath) {
            if (count($files) >= self::MAX_SCAN_FILES) {
                break;
            }

            if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }

            $absolutePath = Storage::disk('local')->path($relativePath);

            try {
                $validated = $this->validatePackage($absolutePath, $module, enforceSchema: false);
                $files[] = $this->descriptor($relativePath, $absolutePath, $validated['manifest']);
            } catch (\Throwable) {
                continue;
            }
        }

        usort($files, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        return array_slice($files, 0, max(1, min($limit, 100)));
    }

    public function resolveLocalReference(string $reference, ?string $expectedModule = null): ?array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $reference)) {
            return null;
        }

        $modules = $expectedModule !== null ? [$expectedModule] : $this->database->getModuleOptions();

        foreach ($modules as $module) {
            if ($module === 'Unknown') {
                continue;
            }

            foreach ($this->listLocal($module, 100) as $snapshot) {
                if (hash_equals($snapshot['reference'], $reference)) {
                    return $snapshot;
                }
            }
        }

        return null;
    }

    public function restore(string $reference, string $module): array
    {
        $snapshot = $this->resolveLocalReference($reference, $module);

        if ($snapshot === null) {
            throw new RuntimeException('Module snapshot local không tồn tại.');
        }

        $validated = $this->validatePackage($snapshot['absolute_path'], $module, enforceSchema: true);
        $lockPath = storage_path('framework/module-restore-'.sha1($module).'.lock');
        $lock = fopen($lockPath, 'c');

        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new RuntimeException('Một tiến trình restore Module này đang chạy.');
        }

        $workingDirectory = storage_path('framework/module-snapshots/'.bin2hex(random_bytes(10)));
        $this->ensureDirectory($workingDirectory);

        try {
            $safety = $this->create($module, 'safety');
            $sqlPath = $this->extractSql($snapshot['absolute_path'], $workingDirectory.'/restore.sql');

            try {
                $this->runMysqlImport($sqlPath, 900);
                DB::purge();
                DB::reconnect();
            } catch (\Throwable $restoreException) {
                try {
                    $safetySql = $this->extractSql($safety['absolute_path'], $workingDirectory.'/safety.sql');
                    $this->runMysqlImport($safetySql, 900);
                    DB::purge();
                    DB::reconnect();
                } catch (\Throwable $rollbackException) {
                    Log::critical('Module restore and automatic rollback both failed.', [
                        'module' => $module,
                        'snapshot' => $snapshot['name'],
                        'safety_snapshot' => $safety['name'],
                        'restore_exception' => $restoreException::class,
                        'rollback_exception' => $rollbackException::class,
                    ]);

                    throw new RuntimeException(
                        'Restore Module thất bại và rollback tự động cũng thất bại. Kiểm tra log hệ thống ngay.',
                        previous: $restoreException,
                    );
                }

                throw new RuntimeException(
                    'Restore Module thất bại. Dữ liệu trước restore đã được phục hồi tự động.',
                    previous: $restoreException,
                );
            }

            Log::notice('Module snapshot restored.', [
                'module' => $module,
                'snapshot' => $snapshot['name'],
                'safety_snapshot' => $safety['name'],
                'tables' => count($validated['manifest']['tables']),
            ]);

            return [
                'snapshot' => $snapshot,
                'safety_snapshot' => $safety,
                'tables' => $validated['manifest']['tables'],
            ];
        } finally {
            $this->deleteDirectory($workingDirectory);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function importDownloadedPackage(string $sourcePath, string $module, string $originalName): array
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('Không thể đọc module snapshot vừa tải.');
        }

        $size = filesize($sourcePath);
        if ($size === false || $size <= 0 || $size > self::MAX_PACKAGE_BYTES) {
            throw new RuntimeException('Module snapshot có dung lượng không hợp lệ.');
        }

        $validated = $this->validatePackage($sourcePath, $module, enforceSchema: false);
        $createdAt = isset($validated['manifest']['created_at'])
            ? \Carbon\Carbon::parse((string) $validated['manifest']['created_at'])
            : now();
        $safeName = basename($originalName);

        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\.zip\z/i', $safeName)) {
            $safeName = 'downloaded_'.strtolower($module).'_'.$createdAt->format('Y-m-d_H-i-s').'.zip';
        }

        $relativePath = sprintf(
            'private/backups/modules/%s/%s/%s/%s',
            $module,
            $createdAt->format('Y'),
            $createdAt->format('m'),
            $safeName,
        );
        $destination = Storage::disk('local')->path($relativePath);
        $this->ensureDirectory(dirname($destination));

        if (is_file($destination)) {
            $safeName = 'downloaded_'.now()->format('Y-m-d_H-i-s-u').'_'.$safeName;
            $relativePath = dirname($relativePath).'/'.$safeName;
            $destination = Storage::disk('local')->path($relativePath);
        }

        if (! copy($sourcePath, $destination)) {
            throw new RuntimeException('Không thể lưu module snapshot vào kho local.');
        }

        return $this->descriptor($relativePath, $destination, $validated['manifest']);
    }

    public function validatePackage(string $path, string $module, bool $enforceSchema = true): array
    {
        $this->tablesForModule($module);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Module snapshot không đọc được.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Module snapshot không phải package ZIP hợp lệ.');
        }

        try {
            $manifestJson = $zip->getFromName('manifest.json');
            $sql = $zip->getFromName('module.sql');
            $checksumsJson = $zip->getFromName('checksums.json');
        } finally {
            $zip->close();
        }

        if (! is_string($manifestJson) || ! is_string($sql) || ! is_string($checksumsJson)) {
            throw new RuntimeException('Module snapshot thiếu manifest, SQL hoặc checksum.');
        }

        $manifest = json_decode($manifestJson, true);
        $checksums = json_decode($checksumsJson, true);

        if (! is_array($manifest) || ! is_array($checksums)) {
            throw new RuntimeException('Manifest module snapshot không hợp lệ.');
        }

        if (($manifest['format_version'] ?? '') !== self::FORMAT_VERSION || ($manifest['module'] ?? '') !== $module) {
            throw new RuntimeException('Module snapshot không đúng Module hoặc phiên bản format.');
        }

        $expectedTables = $this->tablesForModule($module);
        $snapshotTables = array_values(array_filter((array) ($manifest['tables'] ?? []), 'is_string'));
        sort($snapshotTables, SORT_STRING);

        if ($snapshotTables !== $expectedTables) {
            throw new RuntimeException('Danh sách bảng trong snapshot không khớp ownership hiện tại của Module.');
        }

        $expectedChecksum = (string) ($checksums['module.sql'] ?? '');
        if ($expectedChecksum === '' || ! hash_equals($expectedChecksum, hash('sha256', $sql))) {
            throw new RuntimeException('Checksum module snapshot không hợp lệ.');
        }

        $currentSchema = $this->schemaFingerprint($expectedTables);
        $snapshotSchema = (string) ($manifest['schema_fingerprint'] ?? '');
        $compatibility = hash_equals($currentSchema, $snapshotSchema) ? 'COMPATIBLE' : 'BLOCKED';

        if ($enforceSchema && $compatibility !== 'COMPATIBLE') {
            throw new RuntimeException('Schema hiện tại không tương thích với module snapshot đã chọn.');
        }

        return [
            'manifest' => $manifest,
            'checksums' => $checksums,
            'compatibility' => $compatibility,
        ];
    }

    public function localPath(string $reference, string $module): ?string
    {
        return $this->resolveLocalReference($reference, $module)['absolute_path'] ?? null;
    }

    private function descriptor(string $relativePath, string $absolutePath, array $manifest): array
    {
        $module = (string) ($manifest['module'] ?? '');

        return [
            'reference' => $this->reference($relativePath),
            'name' => basename($relativePath),
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'module' => $module,
            'snapshot_type' => (string) ($manifest['snapshot_type'] ?? 'manual'),
            'created_at' => $manifest['created_at'] ?? null,
            'tables' => array_values((array) ($manifest['tables'] ?? [])),
            'size' => (int) (filesize($absolutePath) ?: 0),
            'time' => (int) (filemtime($absolutePath) ?: 0),
            'compatibility' => $this->safeCompatibility($absolutePath, $module),
        ];
    }

    private function safeCompatibility(string $path, string $module): string
    {
        try {
            return $this->validatePackage($path, $module, enforceSchema: false)['compatibility'];
        } catch (\Throwable) {
            return 'BLOCKED';
        }
    }

    private function reference(string $relativePath): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('Application key is required for module snapshot references.');
        }

        return hash_hmac('sha256', 'system-module-snapshot|'.$relativePath, $key);
    }

    private function extractSql(string $packagePath, string $destination): string
    {
        $zip = new ZipArchive;
        if ($zip->open($packagePath) !== true) {
            throw new RuntimeException('Không thể mở module snapshot để restore.');
        }

        try {
            $sql = $zip->getFromName('module.sql');
        } finally {
            $zip->close();
        }

        if (! is_string($sql) || $sql === '' || file_put_contents($destination, $sql) === false) {
            throw new RuntimeException('Không thể chuẩn bị SQL từ module snapshot.');
        }

        return $destination;
    }

    private function schemaFingerprint(array $tables): string
    {
        $definitions = [];

        foreach ($tables as $table) {
            $row = DB::selectOne('SHOW CREATE TABLE `'.str_replace('`', '``', $table).'`');
            $values = $row === null ? [] : array_values((array) $row);
            $definitions[$table] = (string) ($values[1] ?? '');
        }

        ksort($definitions, SORT_STRING);

        return hash('sha256', json_encode($definitions, JSON_UNESCAPED_SLASHES));
    }

    private function rowCounts(string $module): array
    {
        $counts = [];

        foreach ($this->database->getAllTables('', $module) as $table) {
            $counts[$table['name']] = (int) $table['rows'];
        }

        ksort($counts, SORT_STRING);

        return $counts;
    }

    private function runDump(array $tables, string $outputPath, int $timeout): void
    {
        $config = (array) config('database.connections.mysql', []);
        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--user='.($config['username'] ?? ''),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            $config['database'] ?? '',
            ...$tables,
        ];
        $output = fopen($outputPath, 'wb');

        if ($output === false) {
            throw new RuntimeException('Không thể tạo SQL module snapshot.');
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
                Log::error('Module snapshot dump failed.', ['exit_code' => $process->getExitCode()]);
                throw new ProcessFailedException($process);
            }
        } finally {
            fclose($output);
        }
    }

    private function runMysqlImport(string $inputPath, int $timeout): void
    {
        $config = (array) config('database.connections.mysql', []);
        $command = [
            'mysql',
            '--user='.($config['username'] ?? ''),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            $config['database'] ?? '',
        ];
        $input = fopen($inputPath, 'rb');

        if ($input === false) {
            throw new RuntimeException('Không thể mở SQL module snapshot để restore.');
        }

        try {
            $process = new Process($command, null, $this->processEnvironment($config));
            $process->setInput($input);
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('Module snapshot restore import failed.', ['exit_code' => $process->getExitCode()]);
                throw new ProcessFailedException($process);
            }
        } finally {
            fclose($input);
        }
    }

    private function processEnvironment(array $config): array
    {
        return filled($config['password'] ?? null) ? ['MYSQL_PWD' => $config['password']] : [];
    }

    private function assertModuleName(string $module): void
    {
        if (! preg_match('/\A[A-Za-z][A-Za-z0-9_-]{0,79}\z/', $module)) {
            throw new RuntimeException('Tên Module không hợp lệ.');
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException('Không thể tạo thư mục module snapshot.');
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $target = $path.'/'.$entry;
            is_dir($target) ? $this->deleteDirectory($target) : @unlink($target);
        }

        @rmdir($path);
    }
}
