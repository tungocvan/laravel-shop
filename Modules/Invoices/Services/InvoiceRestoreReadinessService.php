<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Schema;

final class InvoiceRestoreReadinessService
{
    public const READY = 'ready';
    public const WARNING = 'warning';
    public const BLOCKED = 'blocked';

    /**
     * Evaluate conditions that are safe to check without mutating application data.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array{status:string,checks:array<int,array<string,mixed>>,warnings:array<int,string>,blockers:array<int,string>}
     */
    public function inspect(array $snapshot): array
    {
        $checks = [];
        $warnings = [];
        $blockers = [];

        $this->check($checks, $blockers, 'manifest', ! empty($snapshot['manifest_valid']), 'Manifest snapshot không hợp lệ.');
        $this->check($checks, $blockers, 'checksum', ! empty($snapshot['checksum_valid']), 'Checksum snapshot không hợp lệ.');
        $this->check($checks, $blockers, 'snapshot_version', ! empty($snapshot['version_supported']), 'Phiên bản snapshot không được hỗ trợ.');

        $schemaReady = Schema::hasTable('invoices') && Schema::hasTable('invoice_files');
        $this->check($checks, $blockers, 'schema', $schemaReady, 'Schema Invoices hiện tại chưa sẵn sàng để restore.');

        if (($snapshot['newer_current_records'] ?? 0) > 0) {
            $warnings[] = number_format((int) $snapshot['newer_current_records']).' hóa đơn hiện tại mới hơn snapshot; nên dùng Merge an toàn.';
        }

        if (array_key_exists('drive_available', $snapshot) && ! $snapshot['drive_available']) {
            $warnings[] = 'Google Drive hiện không khả dụng; metadata có thể restore nhưng khả năng phục hồi PDF chưa được xác nhận.';
        }

        $status = $blockers !== [] ? self::BLOCKED : ($warnings !== [] ? self::WARNING : self::READY);

        return compact('status', 'checks', 'warnings', 'blockers');
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @param  array<int, string>  $blockers
     */
    private function check(array &$checks, array &$blockers, string $code, bool $passed, string $failure): void
    {
        $checks[] = ['code' => $code, 'passed' => $passed];

        if (! $passed) {
            $blockers[] = $failure;
        }
    }
}
