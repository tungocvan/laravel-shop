# Checkpoint 09B — Manual bulk invoice recognition (Git Pull/Test pending)

## Scope
- Finance Documents: checkbox each unlinked invoice and "Chọn trang" selects only currently visible, unlinked invoices (maximum 10).
- Preview shows reference, partner, amount and reasons for failed technical validation. User explicitly attests legal-status and payment review before confirming.
- Per-invoice service call rechecks eligibility inside its own transaction; duplicate sources blocked by existing unique constraint.
- Results: success / skipped / failed with reason for each invoice. No automatic posting of payments, no adjustment of balances, no write to Invoices.
- Only finance.view + invoices-list + finance.debt.manage may perform bulk actions.
- Filter changes reset selection; navigation to another page requires selecting and previewing again. Confirm refuses stale/off-page selections.
- Current invoice schema cannot automatically verify legal cancellation, replacement, adjustments or payments. Reviewer must verify externally; a technical pass does not mean legal validation.

## Git Pull/Test
    cd /var/www/laravel-shop
    git status --short
    git pull --ff-only origin feat/finance-phase2-security-gate-20261024
    php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtRecognitionTest.php
    php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtExplorerUiTest.php
    php artisan test Modules/Finance/Tests

## UI acceptance
- UI30: check 1/2 rows, Chọn trang, deselect; previously linked invoices disabled.
- UI31: preview shows each invoice and clear rejection reason; attestation required.
- UI32: confirm eligible invoice(s), skips invalid; show per-invoice result; no duplicate.
- UI33: change filter or page before confirmation; stale selection rejected; permissions honored.
- UI34: 09A single-invoice review still works, pagination and date filters remain intact.

No local WSL tests executed by assistant. Stop at Git Pull/Test until user PASS.
