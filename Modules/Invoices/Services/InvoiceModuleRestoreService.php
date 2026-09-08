<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class InvoiceModuleRestoreService
{
    public function __construct(
        private readonly InvoiceModuleSnapshotService $snapshots,
        private readonly InvoiceRestoreReadinessService $readiness,
        private readonly InvoiceRestoreImpactService $impact,
        private readonly InvoiceRestoreVerificationService $verification,
    ) {}

    public function restoreMerge(string $directory): array
    {
        $lock = Cache::lock('invoices:module-restore', 600);
        if (! $lock->get()) {
            throw new RuntimeException('Một tiến trình restore Invoices khác đang chạy.');
        }

        try {
            $inspection = $this->snapshots->inspect($directory);
            $manifest = $inspection['manifest'] ?? [];
            $inspection['newer_current_records'] = $this->countNewerCurrentRecords($manifest['created_at'] ?? null);
            $readiness = $this->readiness->inspect($inspection);

            if ($readiness['status'] === InvoiceRestoreReadinessService::BLOCKED) {
                throw new RuntimeException('Snapshot Invoices chưa đủ điều kiện restore: '.implode(' ', $readiness['blockers']));
            }

            $snapshotInvoices = $this->snapshots->readTable($directory, 'invoices');
            $snapshotFiles = $this->snapshots->readTable($directory, 'invoice_files');
            $impact = $this->impact->preview($snapshotInvoices, $snapshotFiles);
            $safetyBackup = $this->createSafetyBackup('safety-before-restore');

            $result = DB::transaction(function () use ($snapshotInvoices, $snapshotFiles): array {
                $current = DB::table('invoices')->get()->mapWithKeys(function ($row): array {
                    $data = (array) $row;

                    return [$this->impact->identity($data) => $data];
                });

                $snapshotIdMap = [];
                $insertedInvoices = 0;
                $preservedInvoices = 0;

                foreach ($snapshotInvoices as $invoice) {
                    $identity = $this->impact->identity($invoice);
                    $existing = $current->get($identity);
                    $snapshotId = isset($invoice['id']) ? (int) $invoice['id'] : null;

                    if ($existing !== null) {
                        $actualId = (int) $existing['id'];
                        $preservedInvoices++;
                    } else {
                        $actualId = (int) DB::table('invoices')->insertGetId($this->invoicePayload($invoice));
                        $insertedInvoices++;
                        $invoice['id'] = $actualId;
                        $current->put($identity, $invoice);
                    }

                    if ($snapshotId !== null) {
                        $snapshotIdMap[$snapshotId] = $actualId;
                    }
                }

                $insertedFiles = 0;
                $preservedFiles = 0;

                foreach ($snapshotFiles as $file) {
                    $sourceInvoiceId = isset($file['invoice_id']) ? (int) $file['invoice_id'] : 0;
                    $actualInvoiceId = $snapshotIdMap[$sourceInvoiceId] ?? null;
                    if ($actualInvoiceId === null) {
                        continue;
                    }

                    if (DB::table('invoice_files')->where('invoice_id', $actualInvoiceId)->exists()) {
                        $preservedFiles++;

                        continue;
                    }

                    DB::table('invoice_files')->insert($this->filePayload($file, $actualInvoiceId));
                    $insertedFiles++;
                }

                return compact('insertedInvoices', 'preservedInvoices', 'insertedFiles', 'preservedFiles');
            });

            $verification = $this->verification->verify($snapshotInvoices);
            if (! $verification['passed']) {
                throw new RuntimeException('Post-restore verification thất bại. Có thể rollback bằng Safety Backup vừa tạo.');
            }

            return [
                'mode' => 'merge',
                'readiness' => $readiness,
                'impact' => $impact,
                'safety_backup' => $safetyBackup,
                'result' => $result,
                'verification' => $verification,
                'partner_master_changes' => 0,
            ];
        } finally {
            $lock->release();
        }
    }

    public function rollbackSafety(string $directory): array
    {
        $lock = Cache::lock('invoices:module-restore', 600);
        if (! $lock->get()) {
            throw new RuntimeException('Một tiến trình restore Invoices khác đang chạy.');
        }

        try {
            $inspection = $this->snapshots->inspect($directory);
            $manifest = $inspection['manifest'] ?? [];
            $mode = (string) ($manifest['mode'] ?? '');

            if (! str_starts_with($mode, 'safety')) {
                throw new RuntimeException('Rollback chính xác chỉ được phép từ Safety Backup.');
            }

            $readiness = $this->readiness->inspect($inspection);
            if ($readiness['status'] === InvoiceRestoreReadinessService::BLOCKED) {
                throw new RuntimeException('Safety Backup chưa đủ điều kiện rollback: '.implode(' ', $readiness['blockers']));
            }

            $snapshotInvoices = $this->snapshots->readTable($directory, 'invoices');
            $snapshotFiles = $this->snapshots->readTable($directory, 'invoice_files');
            $safetyBackup = $this->createSafetyBackup('safety-before-rollback');

            DB::transaction(function () use ($snapshotInvoices, $snapshotFiles): void {
                DB::table('invoice_files')->delete();
                DB::table('invoices')->delete();

                foreach (array_chunk($snapshotInvoices, 250) as $chunk) {
                    DB::table('invoices')->insert(array_map(fn (array $invoice): array => $this->exactInvoicePayload($invoice), $chunk));
                }

                foreach (array_chunk($snapshotFiles, 250) as $chunk) {
                    DB::table('invoice_files')->insert(array_map(fn (array $file): array => $this->exactFilePayload($file), $chunk));
                }
            });

            $verification = $this->verification->verify($snapshotInvoices);
            if (! $verification['passed']) {
                throw new RuntimeException('Post-rollback verification thất bại. Safety Backup mới đã được tạo trước rollback.');
            }

            return [
                'mode' => 'rollback',
                'safety_backup' => $safetyBackup,
                'result' => [
                    'insertedInvoices' => count($snapshotInvoices),
                    'preservedInvoices' => 0,
                    'insertedFiles' => count($snapshotFiles),
                    'preservedFiles' => 0,
                ],
                'verification' => $verification,
                'partner_master_changes' => 0,
            ];
        } finally {
            $lock->release();
        }
    }

    private function createSafetyBackup(string $mode): array
    {
        try {
            return $this->snapshots->create($mode);
        } catch (Throwable $exception) {
            throw new RuntimeException('Không thể tạo Safety Backup; thao tác bị chặn.', previous: $exception);
        }
    }

    private function countNewerCurrentRecords(mixed $createdAt): int
    {
        if (! is_string($createdAt) || $createdAt === '') {
            return 0;
        }

        return DB::table('invoices')->where('created_at', '>', $createdAt)->count();
    }

    private function invoicePayload(array $invoice): array
    {
        return collect($invoice)->only([
            'lookup_code', 'symbol', 'invoice_number', 'type', 'issued_date', 'tax_code', 'name', 'address',
            'email', 'phone', 'tax_rate', 'vat_amount', 'amount_before_vat', 'total_amount', 'invoice_type',
            'created_at', 'updated_at',
        ])->all();
    }

    private function filePayload(array $file, int $invoiceId): array
    {
        $payload = collect($file)->only([
            'provider', 'status', 'path', 'size', 'last_error', 'downloaded_at', 'created_at', 'updated_at',
        ])->all();
        $payload['invoice_id'] = $invoiceId;

        return $payload;
    }

    private function exactInvoicePayload(array $invoice): array
    {
        return collect($invoice)->only([
            'id', 'lookup_code', 'symbol', 'invoice_number', 'type', 'issued_date', 'tax_code', 'name', 'address',
            'email', 'phone', 'tax_rate', 'vat_amount', 'amount_before_vat', 'total_amount', 'invoice_type',
            'created_at', 'updated_at',
        ])->all();
    }

    private function exactFilePayload(array $file): array
    {
        return collect($file)->only([
            'id', 'invoice_id', 'provider', 'status', 'path', 'size', 'last_error', 'downloaded_at', 'created_at', 'updated_at',
        ])->all();
    }
}
