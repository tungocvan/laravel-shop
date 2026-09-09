<?php

namespace Modules\Invoices\Services;

use Carbon\CarbonImmutable;
use Modules\Invoices\Models\Invoices;

final class GdtSyncReadinessService
{
    public function __construct(private readonly GdtApiService $gdt) {}

    public function hasToken(): bool
    {
        return $this->gdt->hasToken();
    }

    public function readiness(?int $year = null): array
    {
        $today = CarbonImmutable::today();
        $year ??= (int) $today->format('Y');
        $yearStart = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $yearEnd = CarbonImmutable::create($year, 12, 31)->endOfDay();

        return [
            'token_ready' => $this->hasToken(),
            'year' => $year,
            'today' => $today->toDateString(),
            'purchase' => $this->directionReadiness('purchase', $yearStart, $yearEnd, $today),
            'sold' => $this->directionReadiness('sold', $yearStart, $yearEnd, $today),
        ];
    }

    public function loadCaptcha(): array
    {
        return $this->gdt->loadCaptcha();
    }

    public function authenticate(string $cvalue, string $ckey): array
    {
        return $this->gdt->login($cvalue, $ckey);
    }

    private function directionReadiness(
        string $invoiceType,
        CarbonImmutable $yearStart,
        CarbonImmutable $yearEnd,
        CarbonImmutable $today,
    ): array {
        $query = Invoices::query()
            ->where('invoice_type', $invoiceType)
            ->whereBetween('issued_date', [$yearStart, $yearEnd]);

        $latest = (clone $query)->max('issued_date');
        $latestDate = $latest ? CarbonImmutable::parse($latest)->toDateString() : null;

        return [
            'latest_invoice_date' => $latestDate,
            'invoice_count' => (clone $query)->count(),
            'suggested_start' => $latestDate ?? $yearStart->toDateString(),
            'suggested_end' => $today->toDateString(),
        ];
    }
}
