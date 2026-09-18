<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;
use RuntimeException;

final class GdtDuplicateRecoveryService
{
    public function recover(int $year = 2026, string $invoiceType = 'sold', bool $apply = false): array
    {
        $pairs = $this->duplicatePairs($year, $invoiceType);
        $stats = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'year' => $year,
            'invoice_type' => $invoiceType,
            'pairs' => $pairs->count(),
            'recovered' => 0,
            'file_refs' => 0,
            'snapshot_refs' => 0,
            'source_records_removed' => 0,
            'blocked' => 0,
            'blocked_reasons' => [],
        ];

        foreach ($pairs as $pair) {
            try {
                $result = $this->recoverPair($pair, $apply);
                $stats['file_refs'] += $result['file_refs'];
                $stats['snapshot_refs'] += $result['snapshot_refs'];
                $stats['source_records_removed'] += $result['source_records_removed'];
                $stats['recovered'] += $apply ? 1 : 0;
            } catch (RuntimeException $exception) {
                $stats['blocked']++;
                $stats['blocked_reasons'][] = $exception->getMessage();
            }
        }

        return $stats;
    }

    private function duplicatePairs(int $year, string $invoiceType): Collection
    {
        return DB::table('invoices as i')
            ->join('invoice_source_records as s', function ($join): void {
                $join->on('s.invoice_id', '=', 'i.id')->where('s.provider', '=', 'gdt');
            })
            ->whereYear('i.issued_date', $year)
            ->where('i.invoice_type', $invoiceType)
            ->whereNotNull('s.header_hash')
            ->whereNotNull('s.detail_hash')
            ->select(
                'i.symbol',
                'i.invoice_number',
                'i.issued_date',
                'i.tax_code',
                'i.total_amount',
                'i.vat_amount',
                's.header_hash',
                's.detail_hash',
                DB::raw('COUNT(*) AS qty'),
            )
            ->groupBy(
                'i.symbol',
                'i.invoice_number',
                'i.issued_date',
                'i.tax_code',
                'i.total_amount',
                'i.vat_amount',
                's.header_hash',
                's.detail_hash',
            )
            ->havingRaw('COUNT(*) = 2')
            ->get();
    }

    private function recoverPair(object $pair, bool $apply): array
    {
        return DB::transaction(function () use ($pair, $apply): array {
            $invoices = Invoices::query()
                ->where('invoice_type', 'sold')
                ->where('symbol', $pair->symbol)
                ->where('invoice_number', $pair->invoice_number)
                ->whereDate('issued_date', $pair->issued_date)
                ->where('tax_code', $pair->tax_code)
                ->where('total_amount', $pair->total_amount)
                ->where('vat_amount', $pair->vat_amount)
                ->whereHas('sourceRecord', fn ($query) => $query
                    ->where('header_hash', $pair->header_hash)
                    ->where('detail_hash', $pair->detail_hash))
                ->lockForUpdate()
                ->get();

            if ($invoices->count() !== 2) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: invariant pair changed.");
            }

            $sources = InvoiceSourceRecord::query()
                ->whereIn('invoice_id', $invoices->pluck('id'))
                ->where('provider', 'gdt')
                ->lockForUpdate()
                ->get()
                ->keyBy('invoice_id');

            if ($sources->count() !== 2) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: thiếu source GDT ở một phía.");
            }

            $canonical = $invoices->first(fn (Invoices $invoice) => $this->isCanonical($invoice, $sources->get($invoice->id)));
            if ($canonical === null) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: không xác định duy nhất canonical GDT identity.");
            }

            $old = $invoices->firstWhere('id', '!=', $canonical->id);
            $canonicalSource = $sources->get($canonical->id);
            $oldSource = $sources->get($old->id);

            if ($canonicalSource->header_hash !== $oldSource->header_hash || $canonicalSource->detail_hash !== $oldSource->detail_hash) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: RAW hash khác nhau, BLOCK.");
            }

            $oldFile = DB::table('invoice_files')->where('invoice_id', $old->id)->first();
            $canonicalFile = DB::table('invoice_files')->where('invoice_id', $canonical->id)->first();
            if ($oldFile !== null && $canonicalFile !== null) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: cả hai phía đều có invoice_file, BLOCK.");
            }

            $oldSnapshot = DB::table('invoice_inventory_snapshots')->where('invoice_id', $old->id)->first();
            $canonicalSnapshot = DB::table('invoice_inventory_snapshots')->where('invoice_id', $canonical->id)->first();
            if ($oldSnapshot !== null && $canonicalSnapshot !== null) {
                throw new RuntimeException("HĐ {$pair->invoice_number}: cả hai phía đều có inventory snapshot, BLOCK.");
            }

            $result = [
                'file_refs' => $oldFile !== null ? 1 : 0,
                'snapshot_refs' => $oldSnapshot !== null ? 1 : 0,
                'source_records_removed' => 1,
            ];

            if (! $apply) {
                return $result;
            }

            $this->mergeSourceMetadata($canonicalSource, $oldSource);

            if ($oldFile !== null) {
                DB::table('invoice_files')->where('id', $oldFile->id)->update(['invoice_id' => $canonical->id]);
            }
            if ($oldSnapshot !== null) {
                DB::table('invoice_inventory_snapshots')->where('id', $oldSnapshot->id)->update(['invoice_id' => $canonical->id]);
            }

            $oldSource->delete();
            $old->delete();

            return $result;
        });
    }

    private function isCanonical(Invoices $invoice, InvoiceSourceRecord $source): bool
    {
        $raw = is_array($source->header_payload) ? $source->header_payload : [];
        foreach (['mtdiep', 'mhdon', 'ma', 'id'] as $key) {
            $value = trim((string) ($raw[$key] ?? ''));
            if ($value !== '') {
                return hash_equals($value, trim((string) $invoice->lookup_code));
            }
        }

        return false;
    }

    private function mergeSourceMetadata(InvoiceSourceRecord $canonical, InvoiceSourceRecord $old): void
    {
        $fields = [
            'business_note',
            'expense_category_id',
            'expense_note',
            'expense_classified_by',
            'expense_classified_at',
        ];

        foreach ($fields as $field) {
            if (blank($canonical->{$field}) && filled($old->{$field})) {
                $canonical->{$field} = $old->{$field};
            }
        }

        if (($canonical->business_classification ?? 'UNCLASSIFIED') === 'UNCLASSIFIED'
            && ($old->business_classification ?? 'UNCLASSIFIED') !== 'UNCLASSIFIED') {
            $canonical->business_classification = $old->business_classification;
            $canonical->classification_scope = $old->classification_scope;
            $canonical->classified_by = $old->classified_by;
            $canonical->classified_at = $old->classified_at;
        }

        $canonical->save();
    }
}
