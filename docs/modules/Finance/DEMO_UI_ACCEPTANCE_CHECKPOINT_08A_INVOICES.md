# Finance / Invoices integration — Checkpoint 08A

Status: Git Pull/Test pending. This is a read-only source reconciliation checkpoint, NOT payment settlement.

## Verified source

Invoices table has invoice_type sold/purchase, issued_date, invoice_number, symbol, name, tax_code, total_amount. Finance debt source fields are source_module, source_type, source_document_id. No validated invoice-payment allocation contract was found in the inspected Invoices model/migrations/routes.

## Delivered

- Documents includes a searchable Invoices table when both finance.view and invoices-list permissions are granted.
- Filter by invoice type, number, symbol, name, tax code; show invoice total and whether Finance has source identity Invoices/invoice/id.
- Read-only; no auto debt creation, settlement, invoice mutation, or payment deductions. Invoice total is not unpaid balance.
- Existing Finance manual debt workflow remains intact.

## Demo WSL

    cd /var/www/laravel-shop
    git status --short
    git pull --ff-only origin feat/finance-phase2-security-gate-20261024
    php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtExplorerUiTest.php
    php artisan test Modules/Finance/Tests

Browser /admin/finance/debts/documents

UI23: Invoice catalog, search and sold/purchase filter; real invoices appear only with enabled module and invoices-list permission.
UI24: source link status; empty results; no claim of paid/unpaid status.
UI25: manual debt creation/confirmation/adjustment remains usable and no invoice writes or payment actions appear.

Stop at Git Pull/Test. Do not deploy. Report any test or UI failure for repair before new checkpoint.
