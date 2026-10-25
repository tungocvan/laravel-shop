# Finance Checkpoint 09A — Single-invoice manual recognition

Status: **Git Pull/Test pending**. No local WSL/browser execution by assistant.

## Scope
- In Finance Documents, authorized users may open an invoice review panel.
- Review shows source reference, direction, counterparty, tax code and total.
- Blocks missing critical data, zero/invalid total, duplicate source, and obvious cancellation/replacement/adjustment keywords in invoice type.
- Explicit human attestation is mandatory because Invoices schema does not establish a complete authoritative legal-status or payment-lifecycle contract. The reviewer must inspect original document, replacement/adjustment state and real payments outside this UI.
- Transactionally creates a confirmed Finance obligation with unique source identity `Invoices/invoice/{id}`, actor, timestamp and optional due date. Does not change invoice or create payment.
- Only `finance.view` + `invoices-list` + `finance.debt.manage` may execute the action.
- 09B bulk processing NOT included. No automatic recognition or settlement.

## Git Pull/Test
    cd /var/www/laravel-shop
    git status --short
    git pull --ff-only origin feat/finance-phase2-security-gate-20261024
    php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtRecognitionTest.php
    php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtExplorerUiTest.php
    php artisan test Modules/Finance/Tests

## Browser
- /admin/finance/debts/documents
- UI26: invoice row opens review; data correct; checkbox mandatory.
- UI27: eligible source confirmed once, row now linked, debt appears in Finance Documents.
- UI28: invalid/duplicate invoice blocked with reasons; no payment shown.
- UI29: view-only account cannot confirm, no bulk confirm; filters/pagination remain functional.

## Safety gate
No production deploy or accounting automation. Human attestation is not a legal validation of invoice status. Check authoritative GDT state before considering automatic recognition.
