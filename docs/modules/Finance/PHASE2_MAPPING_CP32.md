# CP32 — Mapping reconciliation (diagnostic only)

Status: IMPLEMENTED, TEST PENDING. User accepted the following design defaults: partial payments, multi-source vouchers, full reversals only, same-currency payments, and a separate explicit payment link for manual debts. These are design constraints, **not** authorization for actual settlement.

## Implemented

- `FinanceDebtMappingReconciliation::inspect()` delegates to existing CP18 mapping balance invariant and returns `eligible=false`, `authoritative=false` in every case.
- For a numerically consistent mapping set, it returns net and remaining amounts **as diagnostics only**. It does not prove cash, invoice ownership, source allocation or settlement.
- Unconfirmed debts report `debt_not_confirmed`. Overpayments and invalid reversals preserve the invariant reason and suppress diagnostic amounts.
- `assertCanSettle()` always throws. No writer, schema change, production migration, UI change, PR, merge or deploy.

## Deferred — required before authoritative mapping

1. Define and persist immutable ownership of each exact `finance_allocations` row by a debt mapping, including multi-source vouchers and prevention of double assignment.
2. Design explicit manual-debt payment linkage; never infer from unrelated treasury rows.
3. Approve source lifecycle cancellation/adjustment behavior and actor permissions/audit.
4. Verify uniqueness and row-lock protocol for actual post and full reversal writer, including concurrency/rollback.
5. Keep FX/cross-currency and partial reversals out of scope unless separately approved.

## DELL checkpoint

Focused: `php artisan test Modules/Finance/Tests/Feature/FinanceDebtMappingReconciliationTest.php`.

Full: `php artisan test Modules/Finance/Tests` with isolated local MySQL opt-in settings from CP26–29. Zero SKIP required. Stop on FAIL and fix CP32 only.

**Chưa đến CHECKPOINT TEST UI.**
