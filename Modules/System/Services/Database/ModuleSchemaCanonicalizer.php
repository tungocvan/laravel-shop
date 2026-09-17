<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\DB;

/**
 * Read-only canonical schema boundary shared by snapshot validation paths.
 * It compares logical columns, indexes and foreign keys; it never executes DDL.
 */
final class ModuleSchemaCanonicalizer
{
    public function legacySqlMatchesCurrent(string $sql, array $tables): bool
    {
        $snapshot = $this->fromSql($sql, $tables);

        return $snapshot !== [] && $snapshot === $this->current($tables);
    }

    public function current(array $tables): array
    {
        $schema = [];
        $database = (string) DB::connection()->getDatabaseName();

        foreach ($tables as $table) {
            $quoted = '`'.str_replace('`', '``', (string) $table).'`';
            try {
                $columnRows = DB::select('SHOW FULL COLUMNS FROM '.$quoted);
            } catch (\Throwable) {
                return [];
            }

            $columns = [];
            foreach ($columnRows as $row) {
                $data = (array) $row;
                $columns[(string) $data['Field']] = $this->column([
                    'type' => (string) $data['Type'],
                    'null' => (string) $data['Null'],
                    'default' => $data['Default'],
                    'extra' => (string) $data['Extra'],
                ]);
            }
            ksort($columns, SORT_STRING);

            $indexes = [];
            foreach (DB::select('SHOW INDEX FROM '.$quoted) as $row) {
                $data = (array) $row;
                $indexes[(string) $data['Key_name']][] = [
                    'column' => (string) $data['Column_name'],
                    'sequence' => (int) $data['Seq_in_index'],
                    'unique' => (int) $data['Non_unique'] === 0,
                ];
            }

            $foreignKeys = [];
            foreach (DB::select(
                'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION',
                [$database, $table],
            ) as $row) {
                $data = (array) $row;
                $foreignKeys[(string) $data['CONSTRAINT_NAME']][] = [
                    'column' => (string) $data['COLUMN_NAME'],
                    'referenced_table' => (string) $data['REFERENCED_TABLE_NAME'],
                    'referenced_column' => (string) $data['REFERENCED_COLUMN_NAME'],
                ];
            }

            $schema[(string) $table] = [
                'columns' => $columns,
                'indexes' => $this->named($indexes, true),
                'foreign_keys' => $this->named($foreignKeys, false),
            ];
        }

        ksort($schema, SORT_STRING);

        return $schema;
    }

    public function fromSql(string $sql, array $tables): array
    {
        $schema = [];
        foreach ($tables as $table) {
            $quoted = preg_quote((string) $table, '/');
            if (! preg_match('/CREATE TABLE `'.$quoted.'`\s*\((.*?)\)\s*ENGINE=/is', $sql, $match)) {
                return [];
            }

            $columns = [];
            $indexes = [];
            $foreignKeys = [];
            foreach (preg_split('/\R/', (string) $match[1]) ?: [] as $rawLine) {
                $line = trim(rtrim(trim($rawLine), ','));
                if (preg_match('/^`([^`]+)`\s+([^\s]+)(.*)$/i', $line, $columnMatch)) {
                    $tail = (string) $columnMatch[3];
                    $type = (string) $columnMatch[2];
                    if (preg_match('/^\s+unsigned\b/i', $tail)) {
                        $type .= ' unsigned';
                    }
                    $columns[$columnMatch[1]] = $this->column([
                        'type' => $type,
                        'null' => stripos($tail, 'NOT NULL') !== false ? 'NO' : 'YES',
                        'default' => $this->sqlDefault($tail),
                        'extra' => $this->sqlExtra($tail),
                    ]);
                    continue;
                }

                if (preg_match('/^(PRIMARY KEY|UNIQUE KEY `([^`]+)`|KEY `([^`]+)`)\s*\((.+)\)/i', $line, $indexMatch)) {
                    $name = str_starts_with(strtoupper($indexMatch[1]), 'PRIMARY')
                        ? 'PRIMARY'
                        : (($indexMatch[2] ?? '') !== '' ? $indexMatch[2] : $indexMatch[3]);
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
            $schema[(string) $table] = [
                'columns' => $columns,
                'indexes' => $this->named($indexes, true),
                'foreign_keys' => $this->named($foreignKeys, false),
            ];
        }

        ksort($schema, SORT_STRING);

        return $schema;
    }

    private function column(array $definition): array
    {
        $type = strtolower(trim((string) ($definition['type'] ?? '')));
        $type = preg_replace('/\b(tinyint|smallint|mediumint|int|integer|bigint)\(\d+\)/', '$1', $type) ?? $type;
        $type = trim(preg_replace('/\s+/', ' ', $type) ?? $type);
        if (in_array($type, ['json', 'longtext'], true)) {
            $type = 'json-text';
        }

        return [
            'type' => $type,
            'null' => strtoupper((string) ($definition['null'] ?? '')),
            'default' => $definition['default'] ?? null,
            'extra' => strtolower(trim(preg_replace('/\s+/', ' ', (string) ($definition['extra'] ?? '')) ?? '')),
        ];
    }

    private function named(array $structures, bool $index): array
    {
        $normalized = [];
        foreach ($structures as $name => $parts) {
            $items = [];
            foreach ((array) $parts as $part) {
                $items[] = $index
                    ? ['column' => trim((string) ($part['column'] ?? '')), 'sequence' => (int) ($part['sequence'] ?? 0), 'unique' => (bool) ($part['unique'] ?? false)]
                    : ['column' => trim((string) ($part['column'] ?? '')), 'referenced_table' => trim((string) ($part['referenced_table'] ?? '')), 'referenced_column' => trim((string) ($part['referenced_column'] ?? ''))];
            }
            usort($items, static fn (array $a, array $b): int => $index
                ? [$a['sequence'], $a['column']] <=> [$b['sequence'], $b['column']]
                : [$a['column'], $a['referenced_table'], $a['referenced_column']] <=> [$b['column'], $b['referenced_table'], $b['referenced_column']]);
            $normalized[trim((string) $name)] = $items;
        }
        ksort($normalized, SORT_STRING);

        return $normalized;
    }

    private function sqlDefault(string $tail): mixed
    {
        if (! preg_match('/\bDEFAULT\s+(NULL|\'(?:\\.|[^\'])*\'|"(?:\\.|[^"])*"|[^\s,]+)/i', $tail, $match)) {
            return null;
        }
        $token = trim((string) $match[1]);
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }
        if ((str_starts_with($token, "'") && str_ends_with($token, "'")) || (str_starts_with($token, '"') && str_ends_with($token, '"'))) {
            return stripcslashes(substr($token, 1, -1));
        }

        return $token;
    }

    private function sqlExtra(string $tail): string
    {
        $extra = [];
        foreach (['auto_increment', 'on update CURRENT_TIMESTAMP', 'DEFAULT_GENERATED'] as $token) {
            if (stripos($tail, $token) !== false) {
                $extra[] = strtolower($token);
            }
        }

        return implode(' ', $extra);
    }
}
