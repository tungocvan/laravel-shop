<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

class ModuleSnapshotDataService
{
    public function capture(string $module, array $ownedTables, string $directory): array
    {
        $entries = [];
        $schemas = [];
        $rowCounts = [];

        foreach ($ownedTables as $table) {
            $schemas[$table] = $this->tableSchema($table);
            $path = $directory.'/data/owned/'.$table.'.jsonl';
            $rowCounts[$table] = $this->writeQuery($path, DB::table($table)->orderBy($this->stableOrderColumn($table)));
            $entries['data/owned/'.$table.'.jsonl'] = $path;
        }

        $related = [];
        foreach ($this->relatedDefinitions($module) as $definition) {
            $related[] = $this->captureRelated($definition, $directory, $entries, $schemas);
        }

        ksort($schemas, SORT_STRING);
        ksort($rowCounts, SORT_STRING);

        return [
            'entries' => $entries,
            'schema' => $schemas,
            'row_counts' => $rowCounts,
            'related_data' => $related,
        ];
    }

    public function compatibility(array $manifest, array $currentOwnedTables): array
    {
        $snapshotSchema = (array) ($manifest['schema'] ?? []);
        $issues = [];
        $status = 'COMPATIBLE';

        foreach ($snapshotSchema as $table => $columns) {
            if (! $this->tableExists((string) $table)) {
                $issues[] = ['level' => 'warning', 'table' => $table, 'message' => 'Bảng từ snapshot không còn tồn tại; dữ liệu bảng này sẽ được bỏ qua.'];
                $status = $status === 'BLOCKED' ? 'BLOCKED' : 'WARNING';

                continue;
            }

            $current = $this->tableSchema((string) $table);
            $snapshotByName = collect($columns)->keyBy('name');
            $currentByName = collect($current)->keyBy('name');

            foreach ($currentByName as $name => $column) {
                if ($snapshotByName->has($name)) {
                    $old = $snapshotByName->get($name);
                    if (($old['type'] ?? null) !== ($column['type'] ?? null)) {
                        $issues[] = ['level' => 'warning', 'table' => $table, 'column' => $name, 'message' => 'Kiểu cột đã thay đổi; restore sẽ dùng schema production hiện tại.'];
                        $status = $status === 'BLOCKED' ? 'BLOCKED' : 'WARNING';
                    }

                    continue;
                }

                if (! $this->canOmitColumn($column)) {
                    $issues[] = ['level' => 'blocked', 'table' => $table, 'column' => $name, 'message' => 'Cột bắt buộc mới không có default/null; snapshot cũ không thể tạo row hợp lệ.'];
                    $status = 'BLOCKED';
                } else {
                    $issues[] = ['level' => 'info', 'table' => $table, 'column' => $name, 'message' => 'Cột mới sẽ dùng default/null của production.'];
                    if ($status === 'COMPATIBLE') {
                        $status = 'WARNING';
                    }
                }
            }

            foreach ($snapshotByName->keys() as $name) {
                if (! $currentByName->has($name)) {
                    $issues[] = ['level' => 'info', 'table' => $table, 'column' => $name, 'message' => 'Cột cũ không còn tồn tại và sẽ được bỏ qua.'];
                    if ($status === 'COMPATIBLE') {
                        $status = 'WARNING';
                    }
                }
            }
        }

        $snapshotOwned = array_values(array_filter((array) ($manifest['tables'] ?? []), 'is_string'));
        foreach (array_diff($currentOwnedTables, $snapshotOwned) as $table) {
            $issues[] = ['level' => 'info', 'table' => $table, 'message' => 'Bảng mới của Module không có trong snapshot cũ; bảng hiện tại sẽ được giữ nguyên.'];
            if ($status === 'COMPATIBLE') {
                $status = 'WARNING';
            }
        }

        return ['status' => $status, 'issues' => $issues];
    }

    public function restore(string $packagePath, array $manifest): void
    {
        $zip = new ZipArchive;
        if ($zip->open($packagePath) !== true) {
            throw new RuntimeException('Không thể mở Module Snapshot v2.');
        }

        DB::beginTransaction();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $snapshotTables = array_values(array_filter((array) ($manifest['restore_tables'] ?? $manifest['tables'] ?? []), 'is_string'));

            foreach ($snapshotTables as $table) {
                if (! $this->tableExists($table)) {
                    continue;
                }

                DB::table($table)->delete();
                $this->restoreEntry($zip, 'data/owned/'.$table.'.jsonl', $table);
            }

            foreach ((array) ($manifest['related_data'] ?? []) as $related) {
                $this->restoreRelated($zip, (array) $related);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $exception) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable) {
            }
            DB::rollBack();
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    private function captureRelated(array $definition, string $directory, array &$entries, array &$schemas): array
    {
        $name = (string) ($definition['name'] ?? '');
        $root = (array) ($definition['root'] ?? []);
        $rootTable = $this->safeIdentifier((string) ($root['table'] ?? ''));
        $ownerTable = $this->safeIdentifier((string) ($root['owner_table'] ?? ''));
        $ownerKey = $this->safeIdentifier((string) ($root['owner_key'] ?? 'id'));
        $ownerForeignKey = $this->safeIdentifier((string) ($root['owner_foreign_key'] ?? 'owner_id'));
        $whereColumn = $this->safeIdentifier((string) ($root['where_column'] ?? ''));
        $whereValue = (string) ($root['where_value'] ?? '');

        if ($name === '') {
            throw new RuntimeException('Module Snapshot related-data thiếu tên graph.');
        }

        $this->assertRelatedTableColumns($name, $rootTable, [
            (string) ($root['key'] ?? 'id'),
            $whereColumn,
            $ownerForeignKey,
        ]);
        $this->assertRelatedTableColumns($name, $ownerTable, [$ownerKey]);

        $ownerIds = DB::table($ownerTable)->pluck($ownerKey)->all();
        $rootRows = $ownerIds === [] ? collect() : DB::table($rootTable)
            ->where($whereColumn, $whereValue)
            ->whereIn($ownerForeignKey, $ownerIds)
            ->orderBy($this->stableOrderColumn($rootTable))
            ->get();

        $captured = [$rootTable => $rootRows];
        foreach ((array) ($definition['references'] ?? []) as $reference) {
            $reference = (array) $reference;
            $sourceTable = $this->safeIdentifier((string) ($reference['source_table'] ?? ''));
            $sourceColumn = $this->safeIdentifier((string) ($reference['source_column'] ?? ''));
            $targetTable = $this->safeIdentifier((string) ($reference['target_table'] ?? ''));
            $targetColumn = $this->safeIdentifier((string) ($reference['target_column'] ?? 'id'));
            $this->assertRelatedTableColumns($name, $sourceTable, [$sourceColumn]);
            $this->assertRelatedTableColumns($name, $targetTable, [$targetColumn]);

            if (! array_key_exists($sourceTable, $captured)) {
                throw new RuntimeException('Module Snapshot related-data ['.$name.'] tham chiếu source table chưa được capture: '.$sourceTable);
            }

            $sourceRows = $captured[$sourceTable];
            $ids = collect($sourceRows)->pluck($sourceColumn)->filter(fn ($value) => $value !== null)->unique()->values()->all();
            $captured[$targetTable] = $ids === [] ? collect() : DB::table($targetTable)->whereIn($targetColumn, $ids)->orderBy($this->stableOrderColumn($targetTable))->get();
        }

        $rootIds = $rootRows->pluck((string) ($root['key'] ?? 'id'))->all();
        foreach ((array) ($definition['children'] ?? []) as $child) {
            $child = (array) $child;
            $table = $this->safeIdentifier((string) ($child['table'] ?? ''));
            $foreignKey = $this->safeIdentifier((string) ($child['foreign_key'] ?? ''));
            $this->assertRelatedTableColumns($name, $table, [$foreignKey]);
            $captured[$table] = $rootIds === [] ? collect() : DB::table($table)->whereIn($foreignKey, $rootIds)->orderBy($this->stableOrderColumn($table))->get();
        }

        $rowCounts = [];
        foreach ($captured as $table => $rows) {
            if (! $this->tableExists($table)) {
                continue;
            }
            $schemas[$table] = $this->tableSchema($table);
            $path = $directory.'/data/related/'.$name.'/'.$table.'.jsonl';
            $rowCounts[$table] = $this->writeRows($path, $rows);
            $entries['data/related/'.$name.'/'.$table.'.jsonl'] = $path;
        }

        return [
            'name' => $name,
            'root' => $root,
            'references' => array_values((array) ($definition['references'] ?? [])),
            'children' => array_values((array) ($definition['children'] ?? [])),
            'tables' => array_keys($captured),
            'row_counts' => $rowCounts,
        ];
    }

    private function restoreRelated(ZipArchive $zip, array $related): void
    {
        $name = (string) ($related['name'] ?? '');
        $root = (array) ($related['root'] ?? []);
        $rootTable = (string) ($root['table'] ?? '');
        $ownerTable = (string) ($root['owner_table'] ?? '');
        $ownerKey = (string) ($root['owner_key'] ?? 'id');
        $ownerForeignKey = (string) ($root['owner_foreign_key'] ?? 'owner_id');
        $whereColumn = (string) ($root['where_column'] ?? '');
        $whereValue = (string) ($root['where_value'] ?? '');

        if ($name === '' || ! $this->tableExists($rootTable) || ! $this->tableExists($ownerTable)) {
            return;
        }

        $this->assertRelatedRestoreIdentityConflicts($zip, $name, $related);

        $ownerIds = DB::table($ownerTable)->pluck($ownerKey)->all();
        $existingRootIds = $ownerIds === [] ? [] : DB::table($rootTable)
            ->where($whereColumn, $whereValue)
            ->whereIn($ownerForeignKey, $ownerIds)
            ->pluck((string) ($root['key'] ?? 'id'))
            ->all();

        foreach ((array) ($related['children'] ?? []) as $child) {
            $child = (array) $child;
            $table = (string) ($child['table'] ?? '');
            $foreignKey = (string) ($child['foreign_key'] ?? '');
            if ($existingRootIds !== [] && $this->tableExists($table)) {
                DB::table($table)->whereIn($foreignKey, $existingRootIds)->delete();
            }
        }

        if ($existingRootIds !== []) {
            DB::table($rootTable)->whereIn((string) ($root['key'] ?? 'id'), $existingRootIds)->delete();
        }

        foreach ((array) ($related['references'] ?? []) as $reference) {
            $table = (string) ((array) $reference)['target_table'];
            if ($this->tableExists($table)) {
                $this->restoreEntry($zip, 'data/related/'.$name.'/'.$table.'.jsonl', $table, true);
            }
        }

        $this->restoreEntry($zip, 'data/related/'.$name.'/'.$rootTable.'.jsonl', $rootTable, true);

        foreach ((array) ($related['children'] ?? []) as $child) {
            $table = (string) ((array) $child)['table'];
            if ($this->tableExists($table)) {
                $this->restoreEntry($zip, 'data/related/'.$name.'/'.$table.'.jsonl', $table, true);
            }
        }
    }

    private function assertRelatedRestoreIdentityConflicts(ZipArchive $zip, string $name, array $related): void
    {
        $root = (array) ($related['root'] ?? []);
        $rootTable = (string) ($root['table'] ?? '');
        $whereColumn = (string) ($root['where_column'] ?? '');
        $ownerForeignKey = (string) ($root['owner_foreign_key'] ?? 'owner_id');

        foreach ($this->readEntryRows($zip, 'data/related/'.$name.'/'.$rootTable.'.jsonl') as $row) {
            if (! isset($row['id'])) {
                continue;
            }

            $existing = DB::table($rootTable)->where('id', $row['id'])->first();
            if ($existing !== null && (
                (string) ($existing->{$whereColumn} ?? '') !== (string) ($row[$whereColumn] ?? '')
                || (string) ($existing->{$ownerForeignKey} ?? '') !== (string) ($row[$ownerForeignKey] ?? '')
            )) {
                throw new RuntimeException('Module Snapshot related-data ['.$name.'] xung đột ID '.$rootTable.'#'.$row['id'].' với owner khác.');
            }
        }

        foreach ((array) ($related['references'] ?? []) as $reference) {
            $reference = (array) $reference;
            $table = (string) ($reference['target_table'] ?? '');
            if ($table !== 'dossier_templates') {
                continue;
            }

            foreach ($this->readEntryRows($zip, 'data/related/'.$name.'/'.$table.'.jsonl') as $row) {
                if (! isset($row['id'])) {
                    continue;
                }

                $existing = DB::table($table)->where('id', $row['id'])->first();
                if ($existing !== null && isset($row['code']) && (string) ($existing->code ?? '') !== (string) $row['code']) {
                    throw new RuntimeException('Module Snapshot related-data ['.$name.'] xung đột ID '.$table.'#'.$row['id'].' với template code khác.');
                }
            }
        }

        foreach ((array) ($related['children'] ?? []) as $child) {
            $child = (array) $child;
            $table = (string) ($child['table'] ?? '');
            $foreignKey = (string) ($child['foreign_key'] ?? '');
            foreach ($this->readEntryRows($zip, 'data/related/'.$name.'/'.$table.'.jsonl') as $row) {
                if (! isset($row['id'])) {
                    continue;
                }

                $existing = DB::table($table)->where('id', $row['id'])->first();
                if ($existing !== null && (string) ($existing->{$foreignKey} ?? '') !== (string) ($row[$foreignKey] ?? '')) {
                    throw new RuntimeException('Module Snapshot related-data ['.$name.'] xung đột ID '.$table.'#'.$row['id'].' với dossier khác.');
                }
            }
        }
    }

    private function readEntryRows(ZipArchive $zip, string $entry): array
    {
        $raw = $zip->getFromName($entry);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\\R/', trim($raw)) ?: [] as $line) {
            $row = json_decode($line, true);
            if (! is_array($row)) {
                throw new RuntimeException('Dữ liệu JSONL không hợp lệ: '.$entry);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function restoreEntry(ZipArchive $zip, string $entry, string $table, bool $upsert = false): void
    {
        $stream = $zip->getStream($entry);
        if (! is_resource($stream)) {
            return;
        }

        $columns = array_column($this->tableSchema($table), 'name');
        $buffer = [];

        try {
            while (($line = fgets($stream)) !== false) {
                $row = json_decode($line, true);
                if (! is_array($row)) {
                    throw new RuntimeException('Dữ liệu JSONL không hợp lệ: '.$entry);
                }

                $buffer[] = array_intersect_key($row, array_flip($columns));
                if (count($buffer) >= 500) {
                    $this->writeRestoreBatch($table, $buffer, $upsert);
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                $this->writeRestoreBatch($table, $buffer, $upsert);
            }
        } finally {
            fclose($stream);
        }
    }

    private function writeRestoreBatch(string $table, array $rows, bool $upsert): void
    {
        if ($rows === []) {
            return;
        }

        if ($upsert && in_array('id', array_keys($rows[0]), true)) {
            DB::table($table)->upsert($rows, ['id']);

            return;
        }

        DB::table($table)->insert($rows);
    }

    private function writeQuery(string $path, $query): int
    {
        $this->ensureDirectory(dirname($path));
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Không thể tạo dữ liệu Module Snapshot v2.');
        }

        $count = 0;
        try {
            foreach ($query->cursor() as $row) {
                fwrite($handle, json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function writeRows(string $path, $rows): int
    {
        $this->ensureDirectory(dirname($path));
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Không thể tạo related data Module Snapshot v2.');
        }

        $count = 0;
        try {
            foreach ($rows as $row) {
                fwrite($handle, json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function tableSchema(string $table): array
    {
        $database = (string) config('database.connections.mysql.database');
        $rows = DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, ORDINAL_POSITION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$database, $table],
        );

        return array_map(static fn (object $column): array => [
            'name' => (string) $column->COLUMN_NAME,
            'type' => strtolower((string) $column->COLUMN_TYPE),
            'nullable' => (string) $column->IS_NULLABLE === 'YES',
            'default' => $column->COLUMN_DEFAULT,
            'extra' => strtolower((string) $column->EXTRA),
        ], $rows);
    }

    private function canOmitColumn(array $column): bool
    {
        return ($column['nullable'] ?? false)
            || array_key_exists('default', $column) && $column['default'] !== null
            || str_contains((string) ($column['extra'] ?? ''), 'auto_increment')
            || str_contains((string) ($column['extra'] ?? ''), 'generated');
    }

    private function stableOrderColumn(string $table): string
    {
        $columns = array_column($this->tableSchema($table), 'name');

        return in_array('id', $columns, true) ? 'id' : (string) ($columns[0] ?? throw new RuntimeException('Bảng không có cột: '.$table));
    }

    private function tableExists(string $table): bool
    {
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $table)) {
            return false;
        }

        $database = (string) config('database.connections.mysql.database');

        return DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->exists();
    }

    private function assertRelatedTableColumns(string $graph, string $table, array $columns): void
    {
        if (! $this->tableExists($table)) {
            throw new RuntimeException('Module Snapshot related-data ['.$graph.'] khai báo bảng không tồn tại: '.$table);
        }

        $available = array_flip(array_column($this->tableSchema($table), 'name'));
        foreach ($columns as $column) {
            $column = $this->safeIdentifier((string) $column);
            if (! isset($available[$column])) {
                throw new RuntimeException('Module Snapshot related-data ['.$graph.'] khai báo cột không tồn tại: '.$table.'.'.$column);
            }
        }
    }

    private function relatedDefinitions(string $module): array
    {
        $path = base_path('Modules/'.$module.'/config/module.php');
        if (! is_file($path)) {
            return [];
        }

        $config = require $path;

        return array_values((array) data_get($config, 'snapshot.related', []));
    }

    private function safeIdentifier(string $identifier): string
    {
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
            throw new RuntimeException('Module Snapshot related-data identifier không hợp lệ.');
        }

        return $identifier;
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException('Không thể tạo thư mục Module Snapshot v2.');
        }
    }
}
