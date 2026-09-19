<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Models\Invoices;
use Modules\Partner\Models\PartnerSyncCandidate;
use Modules\Partner\Services\PartnerCandidateIntakeService;

final class InvoicePartnerCandidateService
{
    public function __construct(private readonly PartnerCandidateIntakeService $intake) {}

    /**
     * @return array{available:bool,total:int,customers:int,suppliers:int,pending:int,matched:int,conflict:int,ignored:int,missing_identity:int}
     */
    public function summary(): array
    {
        $empty = [
            'available' => false,
            'total' => 0,
            'customers' => 0,
            'suppliers' => 0,
            'pending' => 0,
            'matched' => 0,
            'conflict' => 0,
            'ignored' => 0,
            'missing_identity' => 0,
        ];

        if (! Schema::hasTable('invoices') || ! Schema::hasTable('partner_sync_candidates')) {
            return $empty;
        }

        $taxCodes = Invoices::query()
            ->whereNotNull('tax_code')
            ->where('tax_code', '<>', '');

        $roles = (clone $taxCodes)
            ->selectRaw("COUNT(DISTINCT CASE WHEN invoice_type = 'sold' THEN tax_code END) as customers")
            ->selectRaw("COUNT(DISTINCT CASE WHEN invoice_type = 'purchase' THEN tax_code END) as suppliers")
            ->selectRaw('COUNT(DISTINCT tax_code) as total')
            ->first();

        $statuses = PartnerSyncCandidate::query()
            ->where('source', 'invoices')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched")
            ->selectRaw("SUM(CASE WHEN status = 'conflict' THEN 1 ELSE 0 END) as conflict")
            ->selectRaw("SUM(CASE WHEN status = 'ignored' THEN 1 ELSE 0 END) as ignored")
            ->first();

        return [
            'available' => true,
            'total' => (int) ($roles?->total ?? 0),
            'customers' => (int) ($roles?->customers ?? 0),
            'suppliers' => (int) ($roles?->suppliers ?? 0),
            'pending' => (int) ($statuses?->pending ?? 0),
            'matched' => (int) ($statuses?->matched ?? 0),
            'conflict' => (int) ($statuses?->conflict ?? 0),
            'ignored' => (int) ($statuses?->ignored ?? 0),
            'missing_identity' => Invoices::query()
                ->where(fn ($query) => $query->whereNull('tax_code')->orWhere('tax_code', ''))
                ->count(),
        ];
    }

    /**
     * Aggregate invoice evidence by tax code before handing candidates to Partner.
     *
     * @return array{total:int,pending:int,matched:int,conflict:int,ignored:int}
     */
    public function sync(): array
    {
        $candidates = [];

        Invoices::query()
            ->select(['id', 'tax_code', 'name', 'address', 'email', 'phone', 'invoice_type', 'issued_date'])
            ->whereNotNull('tax_code')
            ->where('tax_code', '<>', '')
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->cursor()
            ->each(function (Invoices $invoice) use (&$candidates): void {
                $taxCode = trim((string) $invoice->tax_code);
                if ($taxCode === '') {
                    return;
                }

                if (! isset($candidates[$taxCode])) {
                    $candidates[$taxCode] = [
                        'tax_code' => $taxCode,
                        'name' => $this->clean($invoice->name),
                        'address' => $this->clean($invoice->address),
                        'email' => $this->clean($invoice->email),
                        'phone' => $this->clean($invoice->phone),
                        'partner_types' => [],
                        'source_metadata' => [
                            'first_invoice_date' => null,
                            'last_invoice_date' => null,
                            'sold_invoice_count' => 0,
                            'purchase_invoice_count' => 0,
                            'last_invoice_id' => $invoice->getKey(),
                        ],
                    ];
                }

                $candidate = &$candidates[$taxCode];
                foreach (['name', 'address', 'email', 'phone'] as $field) {
                    if ($candidate[$field] === null) {
                        $candidate[$field] = $this->clean($invoice->{$field});
                    }
                }

                $role = $invoice->invoice_type === 'sold'
                    ? 'customer'
                    : ($invoice->invoice_type === 'purchase' ? 'supplier' : null);

                if ($role !== null && ! in_array($role, $candidate['partner_types'], true)) {
                    $candidate['partner_types'][] = $role;
                }

                if ($invoice->invoice_type === 'sold') {
                    $candidate['source_metadata']['sold_invoice_count']++;
                } elseif ($invoice->invoice_type === 'purchase') {
                    $candidate['source_metadata']['purchase_invoice_count']++;
                }

                $issuedDate = $invoice->issued_date?->toDateString();
                if ($issuedDate !== null) {
                    $candidate['source_metadata']['last_invoice_date'] ??= $issuedDate;
                    $candidate['source_metadata']['first_invoice_date'] = $issuedDate;
                }

                unset($candidate);
            });

        return $this->intake->intake('invoices', array_values($candidates));
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
