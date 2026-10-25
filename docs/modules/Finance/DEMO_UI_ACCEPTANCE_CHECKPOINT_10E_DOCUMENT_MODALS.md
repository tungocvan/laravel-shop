# Checkpoint 10E — Finance Documents dialog UX (Git Pull/Test pending)

## Scope
- `/admin/finance/debts/documents`: manual debt creation, manual debt detail/adjustment, individual invoice review, bulk preview, and bulk results now use responsive, scrollable dialogs rather than pushing the invoice/debt tables downward.
- Table remains visible behind a dimmed backdrop; dialog has title, close action and `role="dialog" aria-modal="true"`.
- Closing dialogs does not confirm, reverse, or post payments.
- Existing recognition and debt permission checks remain in their Livewire actions.
- No changes to accounting tables, settlement mappings, voucher posting, or principal calculation. This is **not** Checkpoint 11 payment posting.

## Test on DELL WSL
```bash
php artisan test Modules/Finance/Tests/Feature/FinanceDebtDashboardUiTest.php
php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtExplorerUiTest.php
php artisan test Modules/Finance/Tests/Feature/FinanceInvoiceDebtRecognitionTest.php
php artisan test Modules/Finance/Tests
```

## UI acceptance
1. Open Finance > Chứng từ, click **+ Tạo công nợ**; dialog opens, can close without creating a record.
2. Click **Chi tiết** on a debt; details and adjustments appear in dialog; close and reopen; existing draft confirmation still requires permission.
3. Click **Kiểm tra** on an unrecorded invoice; dialog opens, close leaves invoice unrecorded; confirm follows original business validation.
4. Select up to ten invoices and **Xem trước**; per-invoice due dates and review checkbox remain; close leaves all unrecorded.
5. Confirm a valid batch; results dialog shows per-invoice success/skipped/error; close results and verify recorded statuses.
6. Check narrow viewport and long dialog: scroll inside dialog without stretching the table. No automatic payment recognition.
7. Verify finance.view-only users cannot create or confirm debts.

## Next phase
Checkpoint 11A should inspect/reuse existing `finance_vouchers`, `finance_debt_settlement_mappings`, idempotency and settlement service contracts before any new payment schema or posting. Never create a parallel settlement ledger without reconciling existing ownership, reversal, and concurrency gates.
