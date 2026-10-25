# Checkpoint 11E — Confirm invoice debt payments

## Delivered for Git Pull/Test
- `FinanceDebtPaymentPostingService`: transactionally writes Finance source link (if missing), draft-to-posted voucher, exact source allocation, signed ledger entry, debt mapping, allocation ownership and audit event.
- UI enables **Xác nhận thanh toán** only for an eligible confirmed `Invoices/invoice` debt with `finance.debt.manage` and a successfully previewed payment.
- Re-checks invoice amount, sold/purchase direction, VND, account activity, remaining balance and idempotent reference under a debt row lock.
- Supports multiple installments, partial/full balances and duplicate/overpayment rejection.
- Read-only payment history distinguishes verified posted payments; debt table shows posted owned payment totals.
- New integration tests include direct posting and Livewire confirm action.

## Scope / known limitations
- **Only confirmed Invoices/invoice debts**. Manual debts and other source modules remain blocked.
- Existing manual/unowned mappings are not assumed to be paid. Production reversal of a 11E payment is **not yet enabled**; do not reverse posted payment vouchers using legacy treasury screens until a coordinated debt reversal command is delivered.
- The posting service owns its own atomic voucher/ledger transaction; it does not call `VoucherPostingService::post` (which requires a registered document provider for each source). The invoice source is revalidated against `invoices` before posting.
- This is a Git Pull/Test candidate; no claim of WSL/MySQL PASS or production deployment. Review authorization, concurrency and historical data before production rollout.

## Commands
```bash
cd /var/www/laravel-shop
git status --short
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php artisan optimize:clear
php artisan migrate --force
php artisan test Modules/Finance/Tests/Feature/FinanceDebtPaymentPosting11ETest.php
php artisan test Modules/Finance/Tests
```

## Browser acceptance
1. Finance > Chứng từ > Công nợ đã ghi nhận > choose a confirmed invoice-linked debt.
2. Choose account, date, unique reference, amount **20.000.000**, preview, confirm. Verify voucher and ledger and **Thanh toán một phần** with remaining 20.824.000 for an original 40.824.000.
3. Pay remaining **20.824.000** with a different reference; verify **Đã thanh toán**, remaining 0.
4. Repeat same reference and attempt overpayment: must be rejected without any new voucher or ledger.
5. Check finance.debt.manage denied: confirm button not available and server action forbidden.
6. Manual or unverified debt: confirm button remains disabled.

Stop at Git Pull/Test. Do not deploy production until checks pass and payment reversal design is approved.
