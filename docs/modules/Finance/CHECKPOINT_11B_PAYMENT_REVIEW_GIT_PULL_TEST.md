# Checkpoint 11B — Payment review (safe intermediate Git Pull/Test)

## Implemented
- On the **Công nợ đã ghi nhận** tab, confirmed debts expose a **Thanh toán** button.
- Clicking it opens a responsive, read-only review modal with reference, partner, direction, adjusted principal, and exact Finance source-link presence.
- Missing confirmation/source link and the disabled production posting contract are explained in the modal.
- **Xác nhận thanh toán** is disabled. No voucher, allocation, mapping, ledger or paid status is written.
- Server-side `finance.view` is required to open/close; draft debts cannot open payment review.
- Feature regression test checks opening, closing, no financial mutation and draft rejection.

## Why posting is NOT activated
Existing `VoucherPostingService::post` requires provider-backed `finance_source_documents` allocations, whereas invoice-backed debt obligations have an independent source identity and manual debts may have no source at all. Existing `FinanceDebtInternalSettlementWriter` explicitly permits only `testing` environment and cannot be invoked in production. Existing atomic gate rejects all attempts. Treating treasury posting as debt payment would cause inaccurate paid status and reconciliation.

## Next implementation: 11C approved atomic settlement
- Implement provider-backed source bridge with source lifecycle and partner ownership checks.
- Define approved voucher draft and posting authorization, including independent posting rights and reversal.
- Build atomic voucher posting + debt mapping + ownership + audit in one transaction; do not bypass or loosen the test-only internal writer.
- Implement exact-decimal outstanding and payment history, partial/multiple payments, concurrency, idempotency, overpayment, mixed-partner and reversal tests.
- Only then enable **Xác nhận thanh toán** and expose true paid/partial/unpaid statuses.

## Git Pull/Test
```bash
git pull --ff-only origin feat/finance-phase2-security-gate-20261024
php artisan view:clear
php artisan test Modules/Finance/Tests/Feature/FinanceDebtDashboardUiTest.php
php artisan test Modules/Finance/Tests
```
Browser: Finance > Chứng từ > Công nợ đã ghi nhận > Thanh toán; verify review, disabled confirmation and no data change. This is **not** acceptance of live payment posting.
