# CP33 — Exact allocation ownership boundary

**Status: NOT SETTLEMENT READY — test pending.**

This checkpoint adds a concrete read-only preflight for the **exact allocation** belonging to a debt and voucher. It checks debt/voucher/allocation existence, canonical source link, allocation source/voucher identity, confirmed/posted lifecycle, matching currency and direction, and positive allocation amount. All outcomes return `eligible=false`, `authoritative=false`; even structurally consistent allocations have no persistent authoritative mapping ownership. No database writes.

For a **manual debt**, the service rejects inferred treasury ownership and returns `manual_debt_requires_explicit_payment_link`. Multi-source vouchers must be checked by the exact allocation ID, never voucher total. Partial payments are a design requirement, not proof of settlement. Full reversals only; no cross-currency.

## Blocking issues before atomic writer

1. Existing `finance_debt_settlement_mappings` schema has no `allocation_id` foreign key or unique ownership index. Writing settlement now would permit ambiguous double allocation ownership. A reviewed migration and compatibility/rollback plan are required.
2. Manual debt needs a separately approved immutable payment link.
3. Lifecycle, permissions and immutable audit actor/reason are not yet established as an authoritative service contract.
4. Full post/reversal transaction, idempotency and real concurrency races remain unimplemented and unverified.

Therefore **no writer** is implemented or activated in this CP. The original `FinanceDebtAtomicSettlementGate` remains unconditionally fail-closed. This is a safety-driven reduction from the proposed CP33 writer scope; it must not be presented as writer completion.

## Tests and checkpoint

Focused: `php artisan test Modules/Finance/Tests/Feature/FinanceDebtAllocationOwnershipBoundaryTest.php`.

Full: `php artisan test Modules/Finance/Tests` with isolated MySQL opt-in variables to prevent required database tests from SKIP.

**Chưa đến CHECKPOINT TEST UI.** A CHECKPOINT TEST UI will be separately approved only after actual writer, reversal, permissions, concurrency and backend regression are verified.
