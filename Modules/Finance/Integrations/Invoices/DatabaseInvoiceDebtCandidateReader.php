<?php

namespace Modules\Finance\Integrations\Invoices;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Contracts\InvoiceDebtCandidateReader;
use Modules\Finance\Data\InvoiceDebtCandidate;

final class DatabaseInvoiceDebtCandidateReader implements InvoiceDebtCandidateReader
{
    public function find(string $invoiceId): InvoiceDebtCandidate
    {
        if (! (bool) config('modules.registry.Invoices.enabled', false)) {
            throw ValidationException::withMessages(['invoice' => 'Invoices module is disabled.']);
        }

        if (! ctype_digit($invoiceId) || $invoiceId === '0') {
            throw ValidationException::withMessages(['invoice' => 'Invalid invoice identifier.']);
        }

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();

        if ($invoice === null || ! in_array($invoice->invoice_type, ['sold', 'purchase'], true)
            || empty($invoice->issued_date) || trim((string) $invoice->invoice_number) === ''
            || trim((string) $invoice->name) === '') {
            throw ValidationException::withMessages(['invoice' => 'Invoice is incomplete or unavailable.']);
        }

        $amount = (string) $invoice->total_amount;
        if (! preg_match('/^\\d{1,16}(?:\\.\\d{1,2})?$/', $amount)
            || ! preg_match('/[1-9]/', $amount)) {
            throw ValidationException::withMessages(['invoice' => 'Invoice total must be positive.']);
        }

        // Normalize database DECIMAL values without float conversion (SQLite may return 123456).
        $parts = explode('.', $amount, 2);
        $amount = $parts[0].'.'.str_pad($parts[1] ?? '', 2, '0');

        // The local invoice table does not establish commercial eligibility,
        // cancellation/replacement lifecycle, or whether the invoice was already paid.
        // Callers MUST NOT treat this candidate as a recognized obligation.
        return new InvoiceDebtCandidate(
            documentId: (string) $invoice->id,
            documentCode: trim((string) $invoice->invoice_number),
            direction: $invoice->invoice_type === 'sold' ? 'receivable' : 'payable',
            amount: $amount,
            issuedOn: substr((string) $invoice->issued_date, 0, 10),
            counterparty: trim((string) $invoice->name),
            taxCode: ($taxCode = trim((string) $invoice->tax_code)) === '' ? null : $taxCode,
        );
    }
}
