<?php

namespace Modules\Invoices\Services;

use Modules\Invoices\Models\Invoices;

final class InvoiceSourceCoverageService
{
    public function coverage(string $startDate, string $endDate, bool $vatIn): array
    {
        $type = $vatIn ? 'purchase' : 'sold';
        $base = Invoices::query()
            ->where('invoice_type', $type)
            ->whereBetween('issued_date', [$startDate, $endDate]);

        $total = (clone $base)->count();
        $headerReady = (clone $base)
            ->whereHas('sourceRecord', fn ($query) => $query
                ->whereNotNull('header_payload')
                ->whereNotNull('header_hash'))
            ->count();
        $detailReady = (clone $base)
            ->whereHas('sourceRecord', fn ($query) => $query
                ->where('detail_status', 'READY')
                ->whereNotNull('detail_payload')
                ->whereNotNull('detail_hash'))
            ->count();

        return [
            'invoice_type' => $type,
            'total' => $total,
            'header_ready' => $headerReady,
            'detail_ready' => $detailReady,
            'complete' => $total > 0 && $headerReady === $total && $detailReady === $total,
        ];
    }

    public function isComplete(string $startDate, string $endDate, bool $vatIn): bool
    {
        return (bool) $this->coverage($startDate, $endDate, $vatIn)['complete'];
    }
}
