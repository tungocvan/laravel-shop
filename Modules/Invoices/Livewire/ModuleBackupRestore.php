<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Modules\Invoices\Services\InvoiceModuleRestoreService;
use Modules\Invoices\Services\InvoiceModuleSnapshotService;
use Modules\Invoices\Services\InvoiceRestoreImpactService;
use Modules\Invoices\Services\InvoiceRestoreReadinessService;
use Throwable;

class ModuleBackupRestore extends Component
{
    public ?string $selectedSnapshot = null;

    public ?array $readiness = null;

    public ?array $impact = null;

    public ?array $lastRestore = null;

    public ?string $message = null;

    public ?string $error = null;

    public function backupNow(InvoiceModuleSnapshotService $snapshots): void
    {
        $this->resetFeedback();

        try {
            $snapshot = $snapshots->create('manual');
            $this->selectedSnapshot = $snapshot['directory'];
            $this->message = 'Đã tạo backup module Invoices thành công.';
            $this->checkRestore($snapshots, app(InvoiceRestoreReadinessService::class), app(InvoiceRestoreImpactService::class));
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Không thể tạo backup module Invoices: '.$exception->getMessage();
        }
    }

    public function selectSnapshot(string $directory): void
    {
        if (! collect($this->snapshots())->contains(fn (array $snapshot): bool => $snapshot['directory'] === $directory)) {
            return;
        }

        $this->selectedSnapshot = $directory;
        $this->readiness = null;
        $this->impact = null;
        $this->lastRestore = null;
        $this->resetFeedback();
    }

    public function deleteSnapshot(string $directory, InvoiceModuleSnapshotService $snapshots): void
    {
        $this->resetFeedback();

        try {
            $snapshot = collect($this->snapshots())->firstWhere('directory', $directory);
            if (! $snapshot) {
                $this->error = 'Snapshot không còn tồn tại.';

                return;
            }

            $snapshots->delete($directory);
            if ($this->selectedSnapshot === $directory) {
                $this->selectedSnapshot = null;
                $this->readiness = null;
                $this->impact = null;
            }
            $this->message = 'Đã xóa snapshot MANUAL.';
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function rollbackSafety(string $directory, InvoiceModuleRestoreService $restore): void
    {
        $this->resetFeedback();

        $snapshot = collect($this->snapshots())->firstWhere('directory', $directory);
        if (! $snapshot || ! str_starts_with((string) $snapshot['mode'], 'safety')) {
            $this->error = 'Rollback chỉ được phép từ Safety Backup.';

            return;
        }

        try {
            $result = $restore->restoreMerge($directory);
            $this->setLastRestore($result);
            $this->selectedSnapshot = $directory;
            $this->readiness = null;
            $this->impact = null;
            $this->message = 'Rollback từ Safety Backup đã hoàn tất theo chế độ Merge an toàn.';
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Rollback thất bại hoặc bị chặn: '.$exception->getMessage();
        }
    }

    public function checkRestore(
        InvoiceModuleSnapshotService $snapshots,
        InvoiceRestoreReadinessService $readiness,
        InvoiceRestoreImpactService $impact,
    ): void {
        $this->resetFeedback();

        if (! $this->selectedSnapshot) {
            $this->error = 'Hãy chọn một snapshot trước khi kiểm tra khả năng khôi phục.';

            return;
        }

        try {
            $inspection = $snapshots->inspect($this->selectedSnapshot);
            $snapshotInvoices = $inspection['checksum_valid'] && $inspection['version_supported']
                ? $snapshots->readTable($this->selectedSnapshot, 'invoices')
                : [];
            $snapshotFiles = $inspection['checksum_valid'] && $inspection['version_supported']
                ? $snapshots->readTable($this->selectedSnapshot, 'invoice_files')
                : [];

            $this->readiness = $readiness->inspect($inspection);
            $this->impact = $this->readiness['status'] !== InvoiceRestoreReadinessService::BLOCKED
                ? $impact->preview($snapshotInvoices, $snapshotFiles)
                : null;
        } catch (Throwable $exception) {
            report($exception);
            $this->readiness = [
                'status' => InvoiceRestoreReadinessService::BLOCKED,
                'checks' => [],
                'warnings' => [],
                'blockers' => [$exception->getMessage()],
            ];
            $this->impact = null;
        }
    }

    public function restoreMerge(InvoiceModuleRestoreService $restore): void
    {
        $this->resetFeedback();

        if (! $this->selectedSnapshot || ! $this->readiness || $this->readiness['status'] === InvoiceRestoreReadinessService::BLOCKED) {
            $this->error = 'Snapshot chưa vượt qua Restore Readiness Gate.';

            return;
        }

        try {
            $result = $restore->restoreMerge($this->selectedSnapshot);
            $this->setLastRestore($result);
            $this->message = 'Khôi phục Merge an toàn đã hoàn tất và đã chạy hậu kiểm.';
            $this->readiness = null;
            $this->impact = null;
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Khôi phục bị chặn hoặc thất bại: '.$exception->getMessage();
        }
    }

    public function snapshots(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->directories('invoices/module-backups'))
            ->filter(fn (string $directory): bool => $disk->exists($directory.'/manifest.json'))
            ->map(function (string $directory) use ($disk): array {
                $manifest = json_decode($disk->get($directory.'/manifest.json'), true);

                return [
                    'directory' => $directory,
                    'created_at' => is_array($manifest) ? ($manifest['created_at'] ?? null) : null,
                    'mode' => is_array($manifest) ? ($manifest['mode'] ?? 'unknown') : 'unknown',
                    'invoices' => is_array($manifest) ? (int) ($manifest['tables']['invoices'] ?? 0) : 0,
                    'files' => is_array($manifest) ? (int) ($manifest['tables']['invoice_files'] ?? 0) : 0,
                ];
            })
            ->sortByDesc('created_at')
            ->take(20)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('Invoices::livewire.module-backup-restore', [
            'snapshots' => $this->snapshots(),
        ]);
    }

    private function setLastRestore(array $result): void
    {
        $safetyBackup = $result['safety_backup'] ?? null;
        $this->lastRestore = [
            'mode' => (string) ($result['mode'] ?? 'merge'),
            'safety_backup_directory' => is_array($safetyBackup)
                ? (string) ($safetyBackup['directory'] ?? '')
                : (is_string($safetyBackup) ? $safetyBackup : ''),
            'inserted_invoices' => (int) ($result['result']['insertedInvoices'] ?? 0),
            'preserved_invoices' => (int) ($result['result']['preservedInvoices'] ?? 0),
            'inserted_files' => (int) ($result['result']['insertedFiles'] ?? 0),
            'preserved_files' => (int) ($result['result']['preservedFiles'] ?? 0),
            'verification_passed' => (bool) ($result['verification']['passed'] ?? false),
            'partner_master_changes' => (int) ($result['partner_master_changes'] ?? 0),
        ];
    }

    private function resetFeedback(): void
    {
        $this->message = null;
        $this->error = null;
    }
}
