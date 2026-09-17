<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

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

            $steps[] = [
                'risk' => 'REVIEW',
                'action' => 'ADD_INDEX_REVIEW',
                'table' => $issue['table'] ?? null,
                'to' => $missingName,
                'definition' => $definition,
                'message' => "Snapshot có index {$missingName} nhưng database hiện tại chưa có index tương đương.",
                'reason' => 'Cần xác minh migration/foreign key sở hữu index trước khi cho phép ADD INDEX.',
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
