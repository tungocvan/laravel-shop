# Finance Demo UI Checkpoint 05 — Tổng hợp công nợ

Status: **Git Pull/Test pending**. Assistant has not run tests/browser in WSL.

## Delivered
- `/admin/finance/debts/summary`: read-only grouped summary, previously placeholder.
- Only **confirmed** debt obligations; group by stored name, tax code, direction.
- KPIs: count, receivable adjusted principal, payable adjusted principal; table shows original principal, summed signed adjustments, adjusted principal, count and ledger navigation.
- Filter by name/tax, direction and **recognized_on** range, reset, paginate 15 groups.
- Bordered controls and `finance.view` gate.
- No migration, mutations, settlement, invoice sync, accounting opening/closing balances, or production deploy.

## Critical accounting meaning
The date range selects **debt recognition cohorts**, while signed adjustments include all existing events for those selected debts regardless of event date. This is **not** a period movement statement. No beginning/ending balance, cash settlement subtraction, or paid/unpaid state. Missing recognized_on is excluded when date filter is active. Stored name/tax may merge unverified parties. Monetary sums stay in SQL DECIMAL; PHP displays strings without float conversion.

## Test on Demo WSL
1. `git status --short`; `git pull --ff-only origin feat/finance-phase2-security-gate-20261024`.
2. `php artisan test Modules/Finance/Tests/Feature/FinanceDebtSummaryUiTest.php`.
3. `php artisan test Modules/Finance/Tests`.
4. Browser `/admin/finance/debts/summary`: UI14 summary KPI/table, UI15 name/direction/recognition-date filters and reset, UI16 ledger link and border styling. Verify numbers against confirmed manually created debts.
5. Report focused/regression/UI results and error logs. Stop pending user approval.
