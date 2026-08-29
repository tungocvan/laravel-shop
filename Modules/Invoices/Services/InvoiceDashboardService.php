<?php

namespace Modules\Invoices\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Data\InvoiceDashboardData;
use Modules\Invoices\Models\InvoiceBackupRun;
use Modules\Invoices\Models\InvoiceFile;
use Modules\Invoices\Models\Invoices;
use Throwable;

final class InvoiceDashboardService
{
    private const RECENT_LIMIT = 5;

    public function __construct(private readonly GdtApiService $gdtApi) {}

    public function forUser(mixed $user): InvoiceDashboardData
    {
        $capabilities = [
            'list' => $this->hasPermission($user, 'invoices-list'),
            'create' => $this->hasPermission($user, 'invoices-create'),
            'export' => $this->hasPermission($user, 'invoices-export'),
            'download' => $this->hasPermission($user, 'invoices-download'),
            'configure' => $this->hasPermission($user, 'invoices-configure'),
        ];
        $availability = [
            'invoices' => $this->tableExists('invoices'),
            'invoice_files' => $this->tableExists('invoice_files'),
            'invoice_backup_runs' => $this->tableExists('invoice_backup_runs'),
        ];

        $recentInvoices = $availability['invoices'] ? $this->recentInvoices() : [];
        $invoiceMetrics = $availability['invoices']
            ? $this->invoiceMetrics($recentInvoices)
            : $this->emptyInvoiceMetrics(false);
        $pdfMetrics = $this->pdfMetrics(
            $capabilities['download'],
            $availability['invoice_files'] && $invoiceMetrics['available'],
            $invoiceMetrics['total'],
        );
        $backupVisible = $capabilities['download'] || $capabilities['configure'];
        $recentBackupRuns = $backupVisible && $availability['invoice_backup_runs']
            ? $this->recentBackupRuns()
            : [];
        $processing = [
            'sync' => [
                'global_tracking_available' => false,
                'state' => 'workspace_only',
            ],
            'gdt' => $this->gdtStatus($capabilities['configure']),
            'backup' => $this->backupStatus(
                $backupVisible,
                $availability['invoice_backup_runs'],
                $recentBackupRuns,
            ),
        ];

        return new InvoiceDashboardData(
            generatedAt: now()->toIso8601String(),
            capabilities: $capabilities,
            availability: $availability,
            metrics: [
                'invoices' => $invoiceMetrics,
                'pdf' => $pdfMetrics,
            ],
            processing: $processing,
            recentInvoices: $recentInvoices,
            recentBackupRuns: $recentBackupRuns,
            warnings: $this->warnings($availability, $invoiceMetrics, $pdfMetrics, $processing),
        );
    }

    /**
     * @param  array<int, array{type: string, created_at: ?string}>  $recentInvoices
     * @return array{available: bool, total: int, sold: int, purchase: int, latest_at: ?string}
     */
    private function invoiceMetrics(array $recentInvoices): array
    {
        try {
            $summary = Invoices::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN invoice_type = 'sold' THEN 1 ELSE 0 END) as sold")
                ->selectRaw("SUM(CASE WHEN invoice_type = 'purchase' THEN 1 ELSE 0 END) as purchase")
                ->first();

            return [
                'available' => true,
                'total' => (int) ($summary?->total ?? 0),
                'sold' => (int) ($summary?->sold ?? 0),
                'purchase' => (int) ($summary?->purchase ?? 0),
                'latest_at' => $recentInvoices[0]['created_at'] ?? null,
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('invoice_metrics', $exception);

            return $this->emptyInvoiceMetrics(false);
        }
    }

    /**
     * @return array{available: bool, total: int, sold: int, purchase: int, latest_at: ?string}
     */
    private function emptyInvoiceMetrics(bool $available): array
    {
        return [
            'available' => $available,
            'total' => 0,
            'sold' => 0,
            'purchase' => 0,
            'latest_at' => null,
        ];
    }

    /**
     * @return array{visible: bool, available: bool, stored: ?int, error: ?int, missing: ?int}
     */
    private function pdfMetrics(bool $visible, bool $available, int $invoiceTotal): array
    {
        if (! $visible) {
            return [
                'visible' => false,
                'available' => false,
                'stored' => null,
                'error' => null,
                'missing' => null,
            ];
        }

        if (! $available) {
            return [
                'visible' => true,
                'available' => false,
                'stored' => 0,
                'error' => 0,
                'missing' => 0,
            ];
        }

        try {
            $summary = InvoiceFile::query()
                ->selectRaw("SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as stored")
                ->selectRaw("SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors")
                ->first();
            $stored = (int) ($summary?->stored ?? 0);
            $errors = (int) ($summary?->errors ?? 0);

            return [
                'visible' => true,
                'available' => true,
                'stored' => $stored,
                'error' => $errors,
                'missing' => max(0, $invoiceTotal - $stored - $errors),
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('pdf_metrics', $exception);

            return [
                'visible' => true,
                'available' => false,
                'stored' => 0,
                'error' => 0,
                'missing' => 0,
            ];
        }
    }

    /**
     * @return array<int, array{type: string, created_at: ?string}>
     */
    private function recentInvoices(): array
    {
        try {
            return Invoices::query()
                ->select(['invoice_type', 'created_at'])
                ->latest('id')
                ->limit(self::RECENT_LIMIT)
                ->get()
                ->map(fn (Invoices $invoice): array => [
                    'type' => in_array($invoice->invoice_type, ['sold', 'purchase'], true)
                        ? (string) $invoice->invoice_type
                        : 'unknown',
                    'created_at' => $this->iso($invoice->created_at),
                ])
                ->all();
        } catch (Throwable $exception) {
            $this->logUnavailable('recent_invoices', $exception);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentBackupRuns(): array
    {
        try {
            return InvoiceBackupRun::query()
                ->select([
                    'mode',
                    'status',
                    'files_count',
                    'emails_sent',
                    'started_at',
                    'finished_at',
                    'created_at',
                ])
                ->latest('id')
                ->limit(self::RECENT_LIMIT)
                ->get()
                ->map(fn (InvoiceBackupRun $run): array => [
                    'mode' => in_array($run->mode, ['automatic', 'manual'], true)
                        ? (string) $run->mode
                        : 'unknown',
                    'status' => in_array($run->status, ['running', 'skipped', 'success', 'failed'], true)
                        ? (string) $run->status
                        : 'unknown',
                    'files_count' => max(0, (int) $run->files_count),
                    'emails_sent' => max(0, (int) $run->emails_sent),
                    'started_at' => $this->iso($run->started_at ?? $run->created_at),
                    'finished_at' => $this->iso($run->finished_at),
                ])
                ->all();
        } catch (Throwable $exception) {
            $this->logUnavailable('recent_backup_runs', $exception);

            return [];
        }
    }

    /**
     * @return array{visible: bool, available: bool, configured: ?bool, session_available: ?bool}
     */
    private function gdtStatus(bool $visible): array
    {
        if (! $visible) {
            return [
                'visible' => false,
                'available' => false,
                'configured' => null,
                'session_available' => null,
            ];
        }

        try {
            return [
                'visible' => true,
                'available' => true,
                'configured' => filled(config('invoices.gdt.username'))
                    && filled(config('invoices.gdt.password')),
                'session_available' => $this->gdtApi->hasToken(),
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('gdt_status', $exception);

            return [
                'visible' => true,
                'available' => false,
                'configured' => null,
                'session_available' => null,
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $recentRuns
     * @return array<string, mixed>
     */
    private function backupStatus(bool $visible, bool $historyAvailable, array $recentRuns): array
    {
        if (! $visible) {
            return [
                'visible' => false,
                'history_available' => false,
                'automatic_enabled' => null,
                'schedule_day' => null,
                'schedule_time' => null,
                'latest_status' => null,
            ];
        }

        $scheduleTime = (string) config('invoices.backup.schedule_time', '00:15');

        return [
            'visible' => true,
            'history_available' => $historyAvailable,
            'automatic_enabled' => (bool) config('invoices.backup.automatic_enabled', false),
            'schedule_day' => max(1, min(28, (int) config('invoices.backup.schedule_day', 1))),
            'schedule_time' => preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $scheduleTime) === 1
                ? $scheduleTime
                : null,
            'latest_status' => $recentRuns[0]['status'] ?? null,
        ];
    }

    /**
     * @param  array<string, bool>  $availability
     * @param  array<string, mixed>  $invoiceMetrics
     * @param  array<string, mixed>  $pdfMetrics
     * @param  array<string, mixed>  $processing
     * @return array<int, array{level: string, code: string, message: string}>
     */
    private function warnings(array $availability, array $invoiceMetrics, array $pdfMetrics, array $processing): array
    {
        $warnings = [];
        $requiredTables = [
            'invoices' => $availability['invoices'],
        ];

        if ($pdfMetrics['visible']) {
            $requiredTables['invoice_files'] = $availability['invoice_files'];
        }

        if ($processing['backup']['visible']) {
            $requiredTables['invoice_backup_runs'] = $availability['invoice_backup_runs'];
        }

        $missingTables = count(array_filter(
            $requiredTables,
            static fn (bool $available): bool => ! $available,
        ));

        if ($missingTables > 0) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'tables-unavailable',
                'message' => "Có {$missingTables} nhóm dữ liệu chưa sẵn sàng. Hãy kiểm tra migration của Module.",
            ];
        } elseif (! $invoiceMetrics['available']) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'invoice-metrics-unavailable',
                'message' => 'Không thể tải thống kê hóa đơn tại thời điểm này.',
            ];
        }

        if ($pdfMetrics['visible'] && $pdfMetrics['available'] && $pdfMetrics['error'] > 0) {
            $warnings[] = [
                'level' => 'danger',
                'code' => 'pdf-errors',
                'message' => number_format($pdfMetrics['error']).' PDF đang ở trạng thái lỗi.',
            ];
        }

        if ($pdfMetrics['visible'] && $pdfMetrics['available'] && $pdfMetrics['missing'] > 0) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'pdf-missing',
                'message' => number_format($pdfMetrics['missing']).' hóa đơn chưa có PDF khả dụng.',
            ];
        }

        if (($processing['backup']['latest_status'] ?? null) === 'failed') {
            $warnings[] = [
                'level' => 'danger',
                'code' => 'backup-failed',
                'message' => 'Lần backup gần nhất thất bại. Hãy kiểm tra tại workspace đồng bộ.',
            ];
        }

        return $warnings;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable $exception) {
            $this->logUnavailable('table_'.$table, $exception);

            return false;
        }
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        try {
            return Gate::forUser($user)->allows($permission);
        } catch (Throwable) {
            return false;
        }
    }

    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function logUnavailable(string $section, Throwable $exception): void
    {
        Log::warning('Invoices Dashboard section is unavailable.', [
            'section' => $section,
            'exception_class' => $exception::class,
        ]);
    }
}
