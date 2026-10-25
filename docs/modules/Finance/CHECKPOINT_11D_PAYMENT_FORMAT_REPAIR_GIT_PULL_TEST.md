# Checkpoint 11D — VND amount validation repair (partial delivery)

## Changed
- `DebtWorkspace::previewPayment` now validates the text field as input before normalizing Vietnamese separators; the previous raw decimal regex rejected `40.824.000` before normalization.
- Normalized decimal is checked in integer cents and compared with adjusted principal, and the UI is reformatted.
- Unit regression covers grouped VND, raw VND, comma decimals and malformed grouping.

## Not delivered
**Payment confirmation remains disabled.** This is not the requested full 11D production settlement implementation. No posting, allocation, balance reduction, payment statuses or reversal were added. Existing `FinanceDebtAtomicSettlementGate` rejects settlement, `FinanceDebtInternalSettlementWriter` is test-only, and voucher posting requires provider-backed Finance source documents. Activating without those invariants would create misleading financial records.

## Pull and test
```bash
git status --short
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php artisan view:clear
php artisan test Modules/Finance/Tests/Unit/FinancePaymentAmountFormatTest.php
php artisan test Modules/Finance/Tests
```
UI: open payment modal, check prefilled `40.824.000`, fill account/reference, click **Kiểm tra thông tin**. This should validate. **Xác nhận thanh toán** must remain disabled and no financial rows should change.
