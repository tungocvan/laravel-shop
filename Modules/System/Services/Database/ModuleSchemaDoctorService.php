<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/** Read-only diagnostics. This service never executes DDL or bypasses restore validation. */
class ModuleSchemaDoctorService
{
    public function __construct(private readonly ModuleSnapshotService $snapshots) {}

    public function diagnose(string $reference, string $module): array
    {
        $snapshot = $this->snapshots->resolveLocalReference($reference, $module);
        if ($snapshot === null) {
            throw new RuntimeException('Module snapshot local không tồn tại.');
        }

        $validated = $this->snapshots->validatePackage($snapshot['absolute_path'], $module, enforceSchema: false);
        if ($validated['compatibility'] === 'COMPATIBLE') {
            return $this->result('SAFE', 'Schema hiện tại đã tương thích; có thể Restore Module.', []);
        }

        $manifest = $validated['manifest'];
        $snapshotSchema = (array) ($manifest['schema_manifest'] ?? []);
        $source = 'schema_manifest';

        if ($snapshotSchema === []) {
            $snapshotSchema = $this->schemaFromPackageSql($snapshot['absolute_path'], (array) ($manifest['tables'] ?? []));
            $source = 'module.sql';
        }

        if ($snapshotSchema === []) {
            return $this->result('REVIEW', 'Snapshot đời cũ không đủ metadata để xác định khác biệt schema một cách an toàn.', [[
                'type' => 'legacy_snapshot',
                'risk' => 'REVIEW',
                'message' => 'Không đọc được schema_manifest hoặc CREATE TABLE từ module.sql.',
                'suggestion' => 'Đồng bộ cùng branch/commit, kiểm tra migrate:status và migration của Module; không ép bỏ qua validation.',
            ]]);
        }

        $currentSchema = $this->currentSchema((array) ($manifest['tables'] ?? []));
        $issues = $this->compareSchemas($snapshotSchema, $currentSchema, (array) ($manifest['tables'] ?? []));

        $hasBlockedIssue = false;
        foreach ($issues as $issue) {
            if (($issue['risk'] ?? null) === 'BLOCKED') {
                $hasBlockedIssue = true;
                break;
            }
        }
        $verdict = $hasBlockedIssue ? 'BLOCKED' : 'REVIEW';
        $summary = $verdict === 'BLOCKED'
            ? 'Có khác biệt schema có thể làm mất dữ liệu; Doctor khóa auto-repair và Restore.'
            : 'Đã xác định khác biệt schema; cần đối chiếu migration trước khi sửa.';

        if ($source === 'module.sql') {
            $summary .= ' Snapshot cũ được Doctor phân tích trực tiếp từ module.sql.';
        }

        return $this->result($verdict, $summary, $issues);
    }

    /** Canonical, deterministic representation stored by new snapshots and compared by Doctor. */
    public function currentSchema(array $tables): array
    {
        $schema = [];
        foreach ($tables as $table) {
            $quotedTable = '`'.str_replace('`', '``', (string) $table).'`';
            try {
                $columnRows = DB::select('SHOW FULL COLUMNS FROM '.$quotedTable);
            } catch (\Throwable) {
                $schema[$table] = [];
                continue;
            }

            $columns = [];
            foreach ($columnRows as $row) {
                $data = (array) $row;
                $columns[(string) $data['Field']] = [
                    'type' => strtolower((string) $data['Type']),
                    'null' => strtoupper((string) $data['Null']),
                    'default' => $data['Default'],
                    'extra' => strtolower((string) $data['Extra']),
                ];
            }
            ksort($columns, SORT_STRING);

            $indexes = [];
            foreach (DB::select('SHOW INDEX FROM '.$quotedTable) as $row) {
                $data = (array) $row;
                $indexes[(string) $data['Key_name']][] = [
                    'column' => (string) $data['Column_name'],
                    'sequence' => (int) $data['Seq_in_index'],
                    'unique' => (int) $data['Non_unique'] === 0,
                ];
            }
            ksort($indexes, SORT_STRING);

            $foreignKeys = [];
            $database = (string) config('database.connections.mysql.database');
            foreach (DB::select('SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION', [$database, $table]) as $row) {
                $data = (array) $row;
                $foreignKeys[(string) $data['CONSTRAINT_NAME']][] = [
                    'column' => (string) $data['COLUMN_NAME'],
                    'referenced_table' => (string) $data['REFERENCED_TABLE_NAME'],
                    'referenced_column' => (string) $data['REFERENCED_COLUMN_NAME'],
                ];
            }
            ksort($foreignKeys, SORT_STRING);
            $schema[$table] = ['columns' => $columns, 'indexes' => $indexes, 'foreign_keys' => $foreignKeys];
        }
        ksort($schema, SORT_STRING);

        return $schema;
    }

    private function compareSchemas(array $expectedSchema, array $currentSchema, array $tables): array
    {
        $issues = [];
        foreach ($tables as $table) {
            $expected = (array) ($expectedSchema[$table] ?? []);
            $actual = (array) ($currentSchema[$table] ?? []);
            if ($expected === $actual) {
                continue;
            }
            if ($actual === []) {
                $issues[] = $this->issue($table, 'missing_table', 'REVIEW', "Thiếu bảng {$table} so với snapshot.", 'Đối chiếu/chạy migration tạo bảng của Module trước khi restore.');
                continue;
            }

            $expectedColumns = (array) ($expected['columns'] ?? []);
            $actualColumns = (array) ($actual['columns'] ?? []);
            foreach (array_diff_key($expectedColumns, $actualColumns) as $column => $_) {
                $issues[] = $this->issue($table, 'missing_column', 'REVIEW', "Thiếu cột {$table}.{$column}.", 'Đối chiếu migration thêm cột; không sinh DDL trực tiếp từ snapshot.', $column);
            }
            foreach (array_diff_key($actualColumns, $expectedColumns) as $column => $_) {
                $issues[] = $this->issue($table, 'extra_column', 'BLOCKED', "Database hiện tại có thêm cột {$table}.{$column}.", 'Không tự DROP cột. Xác minh version/migration và dữ liệu trước khi quyết định thủ công.', $column);
            }
            foreach (array_intersect_key($expectedColumns, $actualColumns) as $column => $definition) {
                if ($definition !== $actualColumns[$column]) {
                    $issues[] = $this->issue($table, 'column_definition_changed', 'BLOCKED', "Định nghĩa cột {$table}.{$column} khác snapshot.", 'Đối chiếu migration thay đổi kiểu/null/default; không tự ALTER khi chưa đánh giá dữ liệu.', $column);
                }
            }
            foreach (['indexes', 'foreign_keys'] as $section) {
                if ((array) ($expected[$section] ?? []) !== (array) ($actual[$section] ?? [])) {
                    $issues[] = $this->issue($table, $section.'_changed', 'REVIEW', 'Khác biệt '.($section === 'indexes' ? 'index' : 'foreign key')." tại {$table}.", 'Đối chiếu migration tương ứng trước khi sửa schema.');
                }
            }
        }

        return $issues;
    }

    /**
     * Backward-compatible evidence extractor for v1.0 snapshots. It reads only
     * CREATE TABLE statements from the packaged dump; SQL is never executed.
     */
    private function schemaFromPackageSql(string $packagePath, array $tables): array
    {
        $zip = new ZipArchive;
        if ($zip->open($packagePath) !== true) {
            return [];
        }

        try {
            $sql = $zip->getFromName('module.sql');
        } finally {
            $zip->close();
        }

        if (! is_string($sql) || $sql === '') {
            return [];
        }

        $schema = [];
        foreach ($tables as $table) {
            $quoted = preg_quote((string) $table, '/');
            if (! preg_match('/CREATE TABLE `'.$quoted.'`\s*\((.*?)\)\s*ENGINE=/is', $sql, $match)) {
                continue;
            }

            $columns = [];
            $indexes = [];
            $foreignKeys = [];
            foreach (preg_split('/\R/', (string) $match[1]) ?: [] as $rawLine) {
                $line = trim(rtrim(trim($rawLine), ','));
                if (preg_match('/^`([^`]+)`\s+([^\s]+)(.*)$/i', $line, $columnMatch)) {
                    $tail = (string) $columnMatch[3];
                    $default = null;
                    if (preg_match('/\bDEFAULT\s+(NULL|\'((?:\\\'|[^\'])*)\'|([^\s,]+))/i', $tail, $defaultMatch)) {
                        $default = strtoupper((string) $defaultMatch[1]) === 'NULL'
                            ? null
                            : ($defaultMatch[2] !== '' ? stripcslashes($defaultMatch[2]) : $defaultMatch[3]);
                    }
                    $extra = [];
                    foreach (['auto_increment', 'on update CURRENT_TIMESTAMP', 'DEFAULT_GENERATED'] as $token) {
                        if (stripos($tail, $token) !== false) {
                            $extra[] = strtolower($token);
                        }
                    }
                    $columns[$columnMatch[1]] = [
                        'type' => strtolower($columnMatch[2]),
                        'null' => stripos($tail, 'NOT NULL') !== false ? 'NO' : 'YES',
                        'default' => $default,
                        'extra' => implode(' ', $extra),
                    ];
                    continue;
                }

                if (preg_match('/^(PRIMARY KEY|UNIQUE KEY `([^`]+)`|KEY `([^`]+)`)\s*\((.+)\)/i', $line, $indexMatch)) {
                    $name = str_starts_with(strtoupper($indexMatch[1]), 'PRIMARY') ? 'PRIMARY' : ($indexMatch[2] !== '' ? $indexMatch[2] : $indexMatch[3]);
                    $unique = $name === 'PRIMARY' || str_starts_with(strtoupper($indexMatch[1]), 'UNIQUE');
                    preg_match_all('/`([^`]+)`/', $indexMatch[4], $columnMatches);
                    foreach ($columnMatches[1] as $sequence => $column) {
                        $indexes[$name][] = ['column' => $column, 'sequence' => $sequence + 1, 'unique' => $unique];
                    }
                    continue;
                }

                if (preg_match('/^CONSTRAINT `([^`]+)` FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)/i', $line, $fkMatch)) {
                    $foreignKeys[$fkMatch[1]][] = [
                        'column' => $fkMatch[2],
                        'referenced_table' => $fkMatch[3],
                        'referenced_column' => $fkMatch[4],
                    ];
                }
            }

            ksort($columns, SORT_STRING);
            ksort($indexes, SORT_STRING);
            ksort($foreignKeys, SORT_STRING);
            $schema[$table] = ['columns' => $columns, 'indexes' => $indexes, 'foreign_keys' => $foreignKeys];
        }
        ksort($schema, SORT_STRING);

        return $schema;
    }

    private function issue(string $table, string $type, string $risk, string $message, string $suggestion, ?string $subject = null): array
    {
        return array_filter(compact('table', 'subject', 'type', 'risk', 'message', 'suggestion'), static fn ($value): bool => $value !== null);
    }

    private function result(string $verdict, string $summary, array $issues): array
    {
        return ['verdict' => $verdict, 'summary' => $summary, 'issues' => $issues, 'auto_repair_available' => false, 'restore_unlocked' => $verdict === 'SAFE'];
    }
}
