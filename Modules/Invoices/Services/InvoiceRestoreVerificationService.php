<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\DB;

final class InvoiceRestoreVerificationService
{
    public function verify(array $snapshotInvoices): array
    {
        $impact = app(InvoiceRestoreImpactService::class);
        $currentIdentities = DB::table('invoices')->get()->mapWithKeys(function ($row) use ($impact): array {
            $data = (array) $row;

            return [$impact->identity($data) => true];
        });

        $missing = [];
        foreach ($snapshotInvoices as $invoice) {
            $identity = $impact->identity($invoice);
            if (! $currentIdentities->has($identity)) {
                $missing[] = $identity;
            }
        }

        $orphans = DB::table('invoice_files')
            ->leftJoin('invoices', 'invoices.id', '=', 'invoice_files.invoice_id')
            ->whereNull('invoices.id')
            ->count();

        return [
            'passed' => $missing === [] && $orphans === 0,
            'missing_invoice_identities' => $missing,
            'orphan_invoice_files' => $orphans,
            'partner_master_changes' => 0,
        ];
    }
}
