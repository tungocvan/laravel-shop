# Checkpoint 11F — Debt payment reversal

## Implemented
- FinanceDebtPaymentReversalService reverses a single posted, debt-owned invoice payment inside a DB transaction.
- Checks confirmed invoice debt, posted voucher, allocation ownership, source identity, mapping, ledger and duplicate reversal.
- Adds an opposite-signed ledger entry, marks the original voucher reversed, creates an immutable reverse mapping and audit event with actor, reason and timestamp.
- Restores the debt balance using the existing settlement invariant. The original posting and allocation remain for audit.
- Prevents the generic VoucherPostingService from reversing a debt-owned voucher outside the debt transaction.
- Debt detail UI requires a 10–500 character reason, a confirmation prompt and finance.debt.manage authorization.
- Debt evidence reconciles posted + reversed mappings, and the detail view shows reversal reason, actor and time.
- Posting review permits a new payment after a valid reversal.

## Boundaries
- Reversal is available only for a confirmed invoice-linked debt and a single owned posted voucher. It does not delete or edit original ledger rows.
- Reversals are full-voucher only. To correct part of a payment, reverse that voucher and post a new correct amount.
- Never reverse an 11E debt voucher from the generic treasury screen.
- Existing legacy/unowned mappings require reconciliation; do not bypass validations.
- No database migration added; uses existing debt settlement mapping, ownership, idempotency and audit tables.

## Git Pull/Test
```bash
cd /var/www/laravel-shop
git status --short
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php -l Modules/Finance/Services/FinanceDebtPaymentReversalService.php
php -l Modules/Finance/Livewire/DebtWorkspace.php
php artisan optimize:clear
php artisan test Modules/Finance/Tests/Feature/FinanceDebtPaymentPosting11ETest.php
php artisan test Modules/Finance/Tests
```

## Manual acceptance in Demo
1. Choose a confirmed invoice debt with a posted payment. Open **Xem chi tiết** and find its **Lịch sử phiếu thanh toán**.
2. Enter a meaningful reason (at least 10 characters), click **Hoàn tác** for one posted payment, confirm the dialog.
3. Verify original voucher status `reversed`, exactly one `reversal` ledger entry with opposite sign, a reverse mapping and audit reason/actor/time.
4. Verify the outstanding balance increases by precisely the reversed amount; `Đã thanh toán` / `Thanh toán một phần` filters update.
5. Try reversing the same voucher again: must be rejected and no extra ledger entry created.
6. Post a replacement payment with a **new** reference; ensure paid and outstanding figures reconcile.
7. Check a user without `finance.debt.manage`: reversal button unavailable and server action forbidden.

**Stop at Git Pull/Test.** No PHPUnit execution on the user's WSL is claimed; do not deploy to production before review and acceptance.
