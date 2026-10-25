# Checkpoint 11A — Payment activation contract (Git Pull/Test)

## Goal
Activate **actual** payment recognition only after preserving the existing treasury voucher and debt ownership contracts. No production settlement command is exposed by 11A.

## Repository findings
- `finance_vouchers` already owns treasury receipt/payment, account, amount, date, posting/reversal lifecycle and actor.
- `finance_allocations` already connects a treasury voucher to a `finance_source_documents` row, **not directly** to a `finance_debt_obligations` row.
- `finance_debt_settlement_mappings`, `finance_debt_allocation_ownerships` and audit/idempotency structures already exist. Do not add parallel voucher/allocation tables.
- `FinanceDebtInternalSettlementWriter` has a private authorization callback boundary and is explicitly not exposed via HTTP.
- `FinanceDebtAtomicSettlementGate` and `FinanceDebtSettlementContract` intentionally reject public settlement.
- `VoucherPostingService` posts treasury source vouchers but forbids manual voucher allocations. Posting a treasury voucher does **not** by itself prove a debt was settled.
- Existing `FinanceDebtBalanceReader` returns adjusted principal, **not** paid/outstanding balance.

## Required 11B implementation before enabling the button
1. Define exact authoritative bridge for invoice-backed and manual obligations, including partner identity, source lifecycle and currency/direction checks. For manual debt, add a controlled source registration workflow rather than guessing a source link.
2. Authorize separate roles for voucher creation and posting, plus reversal. Use server-side permission checks on every Livewire action; forbid unauthorized self-approval according to configured policy.
3. Create draft receipt/payment with date, bank/cash account, reference, partner, amount and optional attachment; choose one or more confirmed obligations from the same partner and direction.
4. Under a single database transaction, lock obligations, voucher, allocations, source and mapping rows in a deterministic order. Revalidate adjusted principal and remaining balance using exact-decimal arithmetic. Require positive allocations and exact voucher total.
5. Reuse `VoucherPostingService` and approved mapping writer in one auditable lifecycle. Prevent posting treasury successfully while debt mapping fails (or vice versa). Reversal must undo both sides exactly once.
6. Return per-obligation results and derive unpaid/partial/paid **only** from verified, non-reversed authoritative allocations. Do not store a manually toggled `is_paid` flag.
7. Add feature tests: single partial, multiple installments, one voucher across invoices, duplicate click/idempotency, overpayment, mixed partners, unconfirmed debt, permission denial, concurrency, reversal and report projections.
8. Verify MySQL/MariaDB locking and real transaction behavior before exposing posting to production.

## 11A test command
```bash
php artisan test Modules/Finance/Tests/Unit/FinancePaymentActivationBoundaryTest.php
php artisan test Modules/Finance/Tests
```

## Acceptance
11A PASS means the integration boundary is identified and protected. **It does not mean payment is available yet.** Continue only after the user reports tests PASS and approves the next checkpoint.
