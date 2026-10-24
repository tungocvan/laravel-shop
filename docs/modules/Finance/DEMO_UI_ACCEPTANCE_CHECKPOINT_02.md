# Demo UI Checkpoint 02 — Tổng quan công nợ

**Code committed, local WSL test pending.** No assistant-side browser or PHPUnit execution.

## Delivered
- `/admin/finance/debts/dashboard` is now a working, read-only Livewire dashboard, not a placeholder.
- Five cards: count, receivable principal, payable principal, overdue principal, due-today principal. Default status: confirmed.
- Filters: direction, status, partner/reference, due-date from/to, reset. Summary and paginated 15-row list use the same filtered dataset.
- List includes reference, partner, direction, adjusted principal, due date, status, deep link to existing documents detail (`?debt=ID`).
- Permission `finance.view` enforced on render; no write methods, settlement activation or new migrations.
- Six placeholders reduced to five: partners, ledger, summary, aging, settings.

## Critical meaning of amounts
All currency is VND as currently modeled; values are **original principal + signed adjustments**, NOT settlement-adjusted outstanding. Dashboard never subtracts voucher payments and must not be represented as actual unpaid receivables/payables. Overdue means confirmed, due date earlier than current DB date and positive adjusted principal; due today likewise. No due date means neither. Dashboard deliberately does not auto-import Invoices.

## Git Pull/Test on isolated Demo WSL
1. `git status --short`; `git pull --ff-only origin feat/finance-phase2-security-gate-20261024`.
2. `php artisan test Modules/Finance/Tests/Feature/FinanceDebtDashboardUiTest.php`.
3. `php artisan test Modules/Finance/Tests`.
4. Admin browser: open `/admin/finance/debts/dashboard`; confirm cards and list with known manual confirmed debts. Change filters and verify both cards and list respond; reset; verify overdue and due today against due dates. Click `Xem chi tiết`, confirm correct debt opens in documents. Check view-only role cannot mutate. No production operations.

Report focused/full regression and UI separately; if FAIL send error text/screenshot. Do not claim PASS before user runs tests.
