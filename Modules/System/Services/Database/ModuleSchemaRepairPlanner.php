<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\DB;

/**
 * Builds a read-only remediation plan from Schema Doctor evidence.
 * It never executes DDL. Execution is intentionally a separate safety boundary.
 */
class ModuleSchemaRepairPlanner
{
    public function plan(array $report): array
    {
        $steps = [];

        foreach ((array) ($report['issues'] ?? []) as $issue) {
            if (($issue['type'] ?? null) !== 'indexes_changed') {
                $steps[] = $this->reviewStep($issue);
                continue;
            }

            $steps = [...$steps, ...$this->planIndexes($issue)];
        }

        $allSafe = $steps !== [] && collect($steps)->every(
            static fn (array $step): bool => ($step['risk'] ?? null) === 'SAFE'
        );

        return [
            'steps' => $steps,
            'all_safe' => $allSafe,
            // DDL execution remains disabled until the safety-snapshot executor is implemented.
            'execution_available' => false,
        ];
    }

    private function planIndexes(array $issue): array
    {
        $expected = (array) ($issue['snapshot_definition'] ?? []);
        $current = (array) ($issue['current_definition'] ?? []);
        $differences = (array) ($issue['differences'] ?? []);
        $steps = [];

        foreach ((array) ($differences['missing'] ?? []) as $missingName) {
            $definition = (array) ($expected[$missingName] ?? []);
            $equivalentName = $this->equivalentIndexName($definition, $current);

            if ($definition !== [] && $equivalentName !== null) {
                $steps[] = [
                    'risk' => 'SAFE',
                    'action' => 'RENAME_INDEX',
                    'table' => $issue['table'] ?? null,
                    'from' => $equivalentName,
                    'to' => $missingName,
                    'message' => "Index {$equivalentName} có cùng cấu trúc với {$missingName}; khác biệt chỉ là tên index.",
                    'reason' => 'Không thay đổi cột, dữ liệu hay tính unique; chỉ đồng bộ tên metadata theo snapshot.',
                ];
                continue;
            }

            $evidence = $this->collectIndexEvidence((string) ($issue['table'] ?? ''), (string) $missingName, $definition);
            $hasSupportingForeignKey = ($evidence['matching_foreign_keys'] ?? []) !== [];

            $steps[] = [
                'risk' => 'REVIEW',
                'action' => $hasSupportingForeignKey ? 'INDEX_EVIDENCE_CONFLICT_REVIEW' : 'ADD_INDEX_REVIEW',
                'table' => $issue['table'] ?? null,
                'to' => $missingName,
                'definition' => $definition,
                'evidence' => $evidence,
                'message' => $hasSupportingForeignKey
                    ? "Foreign key cho cột của {$missingName} đang tồn tại nhưng SHOW INDEX không có index tương đương."
                    : "Snapshot có index {$missingName} nhưng database hiện tại chưa có index tương đương.",
                'reason' => $hasSupportingForeignKey
                    ? 'Evidence index/FK đang mâu thuẫn; không tự ADD/ALTER. Cần xác minh engine và migration trước.'
                    : 'Cần xác minh migration/foreign key sở hữu index và tính toàn vẹn dữ liệu trước khi cho phép ADD INDEX.',
            ];
        }

        foreach ((array) ($differences['extra'] ?? []) as $extraName) {
            if ($this->isUsedAsRenameSource($extraName, $steps)) {
                continue;
            }

            $steps[] = [
                'risk' => 'BLOCKED',
                'action' => 'KEEP_EXTRA_INDEX',
                'table' => $issue['table'] ?? null,
                'from' => $extraName,
                'message' => "Database hiện tại có thêm index {$extraName}.",
                'reason' => 'Doctor không tự DROP index; cần xác minh migration và workload trước khi thay đổi.',
            ];
        }

        foreach ((array) ($differences['changed'] ?? []) as $changedName) {
            $steps[] = [
                'risk' => 'REVIEW',
                'action' => 'INDEX_DEFINITION_REVIEW',
                'table' => $issue['table'] ?? null,
                'from' => $changedName,
                'message' => "Index {$changedName} tồn tại nhưng cấu trúc khác snapshot.",
                'reason' => 'Không tự ALTER/DROP index khi cấu trúc cột hoặc unique khác nhau.',
            ];
        }

        return $steps === [] ? [$this->reviewStep($issue)] : $steps;
    }

    private function collectIndexEvidence(string $table, string $indexName, array $definition): array
    {
        $columns = collect($definition)
            ->sortBy(static fn (array $part): int => (int) ($part['sequence'] ?? 0))
            ->pluck('column')
            ->filter()
            ->values()
            ->all();

        $evidence = [
            'index' => $indexName,
            'columns' => $columns,
            'matching_foreign_keys' => [],
            'migration_candidates' => [],
        ];

        if ($table !== '' && $columns !== []) {
            try {
                $database = (string) DB::connection()->getDatabaseName();
                $rows = DB::select(
                    'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, ORDINAL_POSITION FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION',
                    [$database, $table],
                );

                $foreignKeys = [];
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $foreignKeys[(string) $data['CONSTRAINT_NAME']][] = [
                        'column' => (string) $data['COLUMN_NAME'],
                        'referenced_table' => (string) $data['REFERENCED_TABLE_NAME'],
                        'referenced_column' => (string) $data['REFERENCED_COLUMN_NAME'],
                    ];
                }

                foreach ($foreignKeys as $name => $parts) {
                    if (array_column($parts, 'column') === $columns) {
                        $evidence['matching_foreign_keys'][$name] = $parts;
                    }
                }
            } catch (\Throwable) {
                $evidence['database_evidence_unavailable'] = true;
            }
        }

        $evidence['migration_candidates'] = $this->migrationCandidates($table, $columns);

        return $evidence;
    }

    private function migrationCandidates(string $table, array $columns): array
    {
        if ($table === '' || $columns === [] || ! function_exists('base_path')) {
            return [];
        }

        $files = glob(base_path('Modules/*/database/migrations/*.php')) ?: [];
        $candidates = [];
        foreach ($files as $file) {
            $source = @file_get_contents($file);
            if (! is_string($source) || ! str_contains($source, $table)) {
                continue;
            }

            $matchesColumns = collect($columns)->every(
                static fn (string $column): bool => str_contains($source, "'{$column}'") || str_contains($source, '"'.$column.'"')
            );
            if (! $matchesColumns) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file, strlen(base_path()) + 1));
            $candidates[] = [
                'file' => $relative,
                'foreign_id' => collect($columns)->contains(
                    static fn (string $column): bool => str_contains($source, "foreignId('{$column}')") || str_contains($source, 'foreignId("'.$column.'")')
                ),
                'constrained' => str_contains($source, '->constrained('),
            ];
        }

        return $candidates;
    }

    private function equivalentIndexName(array $expectedDefinition, array $current): ?string
    {
        if ($expectedDefinition === []) {
            return null;
        }

        foreach ($current as $name => $definition) {
            if ($this->indexSignature((array) $definition) === $this->indexSignature($expectedDefinition)) {
                return (string) $name;
            }
        }

        return null;
    }

    private function indexSignature(array $definition): array
    {
        return collect($definition)
            ->sortBy(static fn (array $part): int => (int) ($part['sequence'] ?? 0))
            ->map(static fn (array $part): array => [
                'column' => (string) ($part['column'] ?? ''),
                'sequence' => (int) ($part['sequence'] ?? 0),
                'unique' => (bool) ($part['unique'] ?? false),
            ])
            ->values()
            ->all();
    }

    private function isUsedAsRenameSource(string $name, array $steps): bool
    {
        return collect($steps)->contains(
            static fn (array $step): bool => ($step['action'] ?? null) === 'RENAME_INDEX' && ($step['from'] ?? null) === $name
        );
    }

    private function reviewStep(array $issue): array
    {
        return [
            'risk' => ($issue['risk'] ?? 'REVIEW') === 'BLOCKED' ? 'BLOCKED' : 'REVIEW',
            'action' => 'MANUAL_REVIEW',
            'table' => $issue['table'] ?? null,
            'message' => $issue['message'] ?? 'Cần đối chiếu schema.',
            'reason' => $issue['suggestion'] ?? 'Cần đối chiếu migration trước khi sửa.',
        ];
    }
}
