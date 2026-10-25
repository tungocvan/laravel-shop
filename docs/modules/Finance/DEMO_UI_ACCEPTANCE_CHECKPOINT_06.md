# Finance Demo UI Checkpoint 06 — Tuổi nợ

Status: **Git Pull/Test pending**. Assistant has not run tests/browser in user's WSL.

## Delivered
- `/admin/finance/debts/aging`: read-only aging screen, previously placeholder.
- Confirmed debts only. Classification as of today's application-local date: future, due today, overdue 1–30, 31–60, 61–90, over 90 days, and no due date.
- Search by partner/tax/reference, direction and aging bucket; reset; seven bucket cards with adjusted principal and counts; paginated detail list with document links.
- Uses SQL DECIMAL sums and view permission; no write actions, migrations, invoice import, settlement or production deploy.

**Accounting meaning:** Buckets classify **original principal plus all signed adjustments**, not unpaid balances. Already paid items are not deducted; do not use for collections decisions as outstanding balances. Due date missing is its own bucket. KPI cards reflect the current filters including selected bucket.

## Demo WSL
1. `git status --short`; `git pull --ff-only origin feat/finance-phase2-security-gate-20261024`.
2. `php artisan test Modules/Finance/Tests/Feature/FinanceDebtAgingUiTest.php`.
3. `php artisan test Modules/Finance/Tests`.
4. Browser `/admin/finance/debts/aging`: UI17 seven aging cards, UI18 search/direction/bucket filters, UI19 list, pagination and **Xem chứng từ** deep link; verify thin input borders.
5. Report focused test, regression and UI outcomes. Stop until user approval.
