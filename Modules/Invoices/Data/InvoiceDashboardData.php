<?php

namespace Modules\Invoices\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class InvoiceDashboardData implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, bool>  $capabilities
     * @param  array<string, bool>  $availability
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>  $processing
     * @param  array<int, array<string, mixed>>  $recentInvoices
     * @param  array<int, array<string, mixed>>  $recentBackupRuns
     * @param  array<int, array{level: string, code: string, message: string}>  $warnings
     */
    public function __construct(
        public string $generatedAt,
        public array $capabilities,
        public array $availability,
        public array $metrics,
        public array $processing,
        public array $recentInvoices,
        public array $recentBackupRuns,
        public array $warnings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'capabilities' => $this->capabilities,
            'availability' => $this->availability,
            'metrics' => $this->metrics,
            'processing' => $this->processing,
            'recent_invoices' => $this->recentInvoices,
            'recent_backup_runs' => $this->recentBackupRuns,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
