# Finance Demo UI Checkpoint 04 — Chi tiết công nợ

Status: **Git Pull/Test pending**. Assistant has not run PHPUnit or WSL browser.

## Delivered
- `/admin/finance/debts/ledger`: actual read-only debt detail ledger; previously placeholder.
- One row per stored obligation; shows reference, partner, receivable/payable, original principal, summed signed adjustments, adjusted principal, recognized date, due date, status.
- Search by reference, partner or tax code; filter direction, status, **due-date** range; reset, 15 rows/page.
- Deep link to `/admin/finance/debts/documents?debt=ID` selects the existing detail.
- Thin input borders, view permission; no write methods, migrations, invoice import or settlement.
- Remaining placeholder sections: summary, aging, settings.

**Financial meaning:** Adjusted principal is not unpaid/outstanding: no subtraction of payments or allocations. Date filters refer to **due_on**, not recognized_on. Missing due dates are omitted when date filter is set.

## Demo WSL
1. `git status --short` and `git pull --ff-only origin feat/finance-phase2-security-gate-20261024`.
2. `php artisan test Modules/Finance/Tests/Feature/FinanceDebtLedgerUiTest.php`.
3. `php artisan test Modules/Finance/Tests`.
4. Browser `/admin/finance/debts/ledger`: compare original, adjustment and adjusted principal with known manually adjusted debts; filter partner/reference, type, status and due dates; reset; verify pagination and thin input borders.
5. Click **Xem chi tiết** and verify selected debt opens in **Chứng từ**. Check a view-only admin can read but cannot mutate.

Send two-tier test results and UI observations. Stop until approval.
