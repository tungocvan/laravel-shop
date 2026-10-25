# Finance Demo UI Checkpoint 03 — Đối tác

Status: **Git Pull/Test pending**. Assistant has not executed local WSL PHPUnit/browser.

## Delivered
- `/admin/finance/debts/partners`: read-only Finance debt partner summary (previously placeholder).
- Group by **counterparty_name + counterparty_tax_code + direction**. Shows count, adjusted principal total and overdue principal total, with 15 groups/page.
- Filters: name/tax, receivable/payable, draft/confirmed; defaults to confirmed. Bordered inputs.
- Link `Xem khoản nợ` to dashboard with prefilled name/direction filters.
- Permission `finance.view` checked. No mutations, new migrations, or settlement activation.
- Remaining placeholders: ledger, summary, aging, settings.

**Accounting limitation:** Grouping by names/tax codes in stored obligations does not imply verified identity in Partner module. Missing tax code can merge same-name parties. Totals are original principal plus adjustments, **not unpaid balance**. Overdue means due date earlier than today and confirmed with positive principal. This does not import invoices or calculate settlement.

## Test on isolated Demo WSL
1. `git status --short`; `git pull --ff-only origin feat/finance-phase2-security-gate-20261024`.
2. `php artisan test Modules/Finance/Tests/Feature/FinanceDebtPartnersUiTest.php`.
3. `php artisan test Modules/Finance/Tests`.
4. In browser visit `/admin/finance/debts/partners`; confirm table and grouping; filter by name, direction and status; reset; click `Xem khoản nợ` and confirm dashboard filters reflect chosen partner. Verify borders/focus. Compare totals with known manual debt principal and adjustments. No write actions on this screen.

Report both test layers and UI separately. If FAIL send error text/screenshot. Do not proceed to another UI checkpoint until approval.
