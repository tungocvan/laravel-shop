<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Read-only schema diagnostics for Module snapshots.
 *
 * The doctor deliberately does not execute DDL. Repair remains an explicit,
 * separately reviewed operation so a BLOCKED snapshot can never become a
 * destructive "force restore" path.
 */
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
        $manifest = $validated['manifest'];
        $snapshotSchema = (array) ($manifest['schema_manifest'] ?? []);
        $currentSchema = $this->snapshots->schemaManifest((array) ($manifest['tables'] ?? []));

        if ($validated['compatibility'] === 'COMPATIBLE') {
            return $this->result('SAFE', 'Schema hiện tại đã tương thích; có thể Restore Module.', [], $snapshotSchema !== []);
        }

        if ($snapshotSchema === []) {
            return $this->result(
                'REVIEW',
                'Snapshot đời cũ chỉ có fingerprint tổng nên chưa đủ bằng chứng để tự sửa schema an toàn.',
                [[
                    'type' => 'legacy_snapshot',
                    'risk' => 'REVIEW',
                    'message' => 'Không có schema_manifest chi tiết để xác định chính xác cột/index khác biệt.',
                    'suggestion' => 'Đồng bộ cùng branch/commit, kiểm tra migrate:status và migration của Module; không ép bỏ qua validation.',
                ]],
                false,
            );
        }

        $issues = [];
        foreach ((array) ($manifest['tables'] ?? []) as $table) {
            $expected = (array) ($snapshotSchema[$table] ?? []);
            $actual = (array) ($currentSchema[$table] ?? []);

            if ($expected === $actual) {
                continue;
            }

            if ($actual === []) {
                $issues[] = [
                    'table' => $table,
                    'type' => 'missing_table',
                    'risk' => 'REVIEW',
                    'message' => "Thiếu bảng {$table} so với snapshot.",
                    'suggestion' => 'Chạy/đối chiếu migration tạo bảng của Module trước khi restore.',
                ];
                continue;
            }

            $expectedColumns = (array) ($expected['columns'] ?? []);
            $actualColumns = (array) ($actual['columns'] ?? []);
            foreach (array_diff_key($expectedColumns, $actualColumns) as $column => $_definition) {
                $issues[] = [
                    'table' => $table,
                    'subject' => $column,
                    'type' => 'missing_column',
                    'risk' => 'REVIEW',
                    'message' => "Thiếu cột {$table}.{$column}.",
                    'suggestion' => 'Đối chiếu migration thêm cột; chỉ áp dụng migration đã được version-control, không sinh DDL từ snapshot.',
                ];
            }
            foreach (array_diff_key($actualColumns, $expectedColumns) as $column => $_definition) {
                $issues[] = [
                    'table' => $table,
                    'subject' => $column,
                    'type' => 'extra_column',
                    'risk' => 'BLOCKED',
                    'message' => "Database hiện tại có thêm cột {$table}.{$column} không tồn tại trong snapshot.",
                    'suggestion' => 'Không tự DROP cột. Xác minh version/migration và dữ liệu trước khi quyết định thủ công.',
                ];
            }
            foreach (array_intersect_key($expectedColumns, $actualColumns) as $column => $definition) {
                if ($definition !== $actualColumns[$column]) {
                    $issues[] = [
                        'table' => $table,
                        'subject' => $column,
                        'type' => 'column_definition_changed',
                        'risk' => 'BLOCKED',
                        'message' => "Định nghĩa cột {$table}.{$column} khác snapshot.",
                        'suggestion' => 'Đối chiếu migration thay đổi kiểu/null/default; không tự ALTER khi chưa đánh giá dữ liệu.',
                    ];
                }
            }

            foreach (['indexes', 'foreign_keys'] as $section) {
                $expectedItems = (array) ($expected[$section] ?? []);
                $actualItems = (array) ($actual[$section] ?? []);
                if ($expectedItems !== $actualItems) {
                    $issues[] = [
                        'table' => $table,
                        'type' => $section.'_changed',
                        'risk' => 'REVIEW',
                        'message' => 'Khác biệt '.($section === 'indexes' ? 'index' : 'foreign key')." tại {$table}.",
                        'suggestion' => 'Đối chiếu migration tương ứng trước khi sửa schema.',
                    ];
                }
            }
        }

        $verdict = collect($issues)->contains(fn (array $issue): bool => $issue['risk'] === 'BLOCKED') ? 'BLOCKED' : 'REVIEW';
        $summary = $verdict === 'BLOCKED'
            ? 'Có khác biệt schema có thể làm mất dữ liệu; Doctor khóa auto-repair và Restore.'
            : 'Đã xác định khác biệt nhưng cần đối chiếu migration trước khi sửa.';

        return $this->result($verdict, $summary, $issues, true);
    }

    private function result(string $verdict, string $summary, array $issues, bool $hasDetailedManifest): array
    {
        return [
            'verdict' => $verdict,
            'summary' => $summary,
            'issues' => $issues,
            'has_detailed_manifest' => $hasDetailedManifest,
            'auto_repair_available' => false,
            'restore_unlocked' => $verdict === 'SAFE',
        ];
    }
}
