# Checkpoint 11C — Payment preparation, not live posting

## Implemented
- Payment modal now includes amount, payment date, active account (same currency), and transaction reference.
- Server validates date, decimal precision, account ownership/currency and positive amount not exceeding **adjusted principal**.
- Preview state resets when any field changes.
- No database mutation occurs; the **Xác nhận thanh toán** action stays disabled.

## Remaining blockers before production payment activation
- Adjusted principal is not authoritative outstanding; preview maximum is a *preliminary bound only*.
- Invoice-backed debts have no verified `finance_source_documents` registration/provider for treasury posting.
- Manual debts cannot be posted as allocated source vouchers under existing `VoucherPostingService`.
- Existing internal settlement writer is explicitly test-only; production atomic gate is fail-closed.
- Need approved provider-backed bridge, transactionally consistent voucher+allocation+mapping, server permissions for posting/reversal, exact paid/outstanding projection and contention tests.

## Test
```bash
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php artisan view:clear
php artisan test Modules/Finance/Tests/Unit/FinancePaymentPreparationContractTest.php
php artisan test Modules/Finance/Tests
```
UI: Finance > Chứng từ > Công nợ đã ghi nhận > Thanh toán > fill fields > Kiểm tra thông tin. Verify the confirm button remains disabled and balances are unchanged.

**Do not treat 11C PASS as payment activation.**
