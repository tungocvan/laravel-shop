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

## UI/UX refinement and 90-day due-date rule (Git Pull/Test pending)
- Cleaner invoice source heading, compact selected count/amount/technical-readiness summary, shortened status labels and single-invoice action.
- Bulk preview displays invoice issuance date and **editable per-invoice due date** defaulted to issue date + **90 calendar days** (not +3 months); single-invoice review defaults likewise.
- No shared due date for the whole batch. Invalid or missing issuance date has no default and fails the source validation.
- A historical invoice can have a due date earlier than the recognition date; this is valid for overdue aging.
- Collapsible per-invoice results with success/skipped/failed counts and reasons.
- Existing Finance obligations are not retroactively changed. No payments or legal invoice verification are automated.
- Check PHP unit tests and UI30–34 again, plus UI35 per-invoice default due dates (12/10/2026 -> 10/01/2027; 11/10/2026 -> 09/01/2027), UI36 edit one due date only, UI37 historical overdue invoice, UI38 collapse/expand results.
