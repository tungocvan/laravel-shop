<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\DB;

final class InvoiceRestoreImpactService
{
    public function preview(array $snapshotInvoices, array $snapshotFiles = []): array
    {
        $current = DB::table('invoices')->get()->mapWithKeys(function ($row): array {
            $data = (array) $row;

            return [$this->identity($data) => $data];
        });

        $insert = 0;
        $existing = 0;
        $different = 0;

        foreach ($snapshotInvoices as $invoice) {
            $key = $this->identity($invoice);
            $currentInvoice = $current->get($key);
            if ($currentInvoice === null) {
                $insert++;

                continue;
            }

            $existing++;
            if ($this->comparable($currentInvoice) !== $this->comparable($invoice)) {
                $different++;
            }
        }

        return [
            'invoices' => [
                'insert' => $insert,
                'existing' => $existing,
                'different' => $different,
                'delete' => 0,
            ],
            'invoice_files' => [
                'snapshot' => count($snapshotFiles),
            ],
            'partner_master_changes' => 0,
            'recommended_mode' => 'merge',
        ];
    }

    public function identity(array $invoice): string
    {
        $invoiceType = strtolower(trim((string) ($invoice['invoice_type'] ?? '')));
        $lookupCode = trim((string) ($invoice['lookup_code'] ?? ''));

        if ($lookupCode !== '') {
            return implode('|', ['lookup', $invoiceType, $lookupCode]);
        }

        return implode('|', [
            'composite',
            $invoiceType,
            trim((string) ($invoice['symbol'] ?? '')),
            trim((string) ($invoice['invoice_number'] ?? '')),
            trim((string) ($invoice['tax_code'] ?? '')),
            substr((string) ($invoice['issued_date'] ?? ''), 0, 10),
        ]);
    }

    private function comparable(array $invoice): array
    {
        unset($invoice['id'], $invoice['created_at'], $invoice['updated_at']);
        ksort($invoice);

        return $invoice;
    }
}
