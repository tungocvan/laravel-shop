# Checkpoint 11G — Read-only debt reconciliation (first gate)

Implemented a third tab **Đối soát 11G** in the existing debt document workspace.
It shows confirmed receivable/payable adjusted principal, net settlement mappings (posts minus reversals), expected outstanding and diagnostic anomalies. Totals marked **đã đối soát** exclude flagged debts; the suspected outstanding is shown per row for investigation only.

The anomaly gate flags missing mapping ownership, invalid voucher status, reversed posts without reverse mappings, missing reversal ledger, missing posting ledger, invalid reverse origins and negative/over-principal net settlement.

**Scope:** Current cumulative snapshot, not accounting-period opening/closing balances, not a statutory financial report. Period movements and historical as-of snapshots require a separate dated ledger/event projection and will be added after this first gate passes. This tab does not authorize or change any payment. It cannot independently certify all legacy treasury allocations; reconcile externally allocated invoices before relying on the totals.

## Pull/Test

```bash
cd /var/www/laravel-shop
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php -l Modules/Finance/Services/FinanceDebtReconciliation11G.php
php -l Modules/Finance/Livewire/DebtWorkspace.php
php artisan optimize:clear
php artisan test Modules/Finance/Tests/Unit/FinanceDebtReconciliation11GContractTest.php
php artisan test Modules/Finance/Tests
```

## UI acceptance
- Open **Finance → Chứng từ công nợ → Đối soát 11G**.
- Verify **Phải thu / Phải trả** and search filters, page totals, pagination and role-based access.
- Compare a fully paid, partially paid and reversed invoice against its 11E/11F payment history.
- Check that reversed payments increase expected outstanding, without deleting original vouchers.
- Check that a malformed mapping shows **Cần đối soát**, not a falsely verified outstanding.
- Do not use as period opening/closing report or production ledger reconciliation.

**Stop at Git Pull/Test**; local PHPUnit/UI acceptance must be confirmed by the user.
