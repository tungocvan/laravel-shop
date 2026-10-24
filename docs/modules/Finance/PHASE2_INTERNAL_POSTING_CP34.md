# CP34 — Isolated internal posting primitive

Status: **CODE COMMITTED / TEST PENDING — NOT PRODUCTION READY**.

## Included

- Additive `finance_debt_allocation_ownerships` migration, with unique `allocation_id` and unique `mapping_id` ownership, actor and authorization reason. Migration is committed but **must not be run on production**. Existing mappings are not backfilled or considered authoritative.
- `FinanceDebtInternalSettlementWriter::post()`: restricted to Laravel `testing` environment; requires explicit authorization callback returning true, actor and reason. Locks debt, voucher and exact allocation, validates source identity/lifecycle/currency/direction, validates existing mapping balance and amount, reserves idempotency token, creates mapping and exact allocation ownership in a single transaction. Database unique indexes enforce duplicate prevention.
- Focused integration tests for atomic insert, denied authorization, repeated allocation and overpayment. Existing `FinanceDebtAtomicSettlementGate` is unchanged and always rejects public settlement.

## Not included / blockers

- **Reversal remains explicitly denied**, pending approved lifecycle, reversal voucher ownership, release of allocation ownership, and two-connection race/rollback tests.
- No production-grade permission policy integration; the callback is a test boundary, not a substitute for a real authorization service.
- No approval of manual-debt payment ownership, legacy mapping backfill, cancellation/adjustment lifecycle, or actual invoice ownership.
- No production migration, deployment, UI, route/controller, PR or merge. Existing posted treasury vouchers are not proof of actual debt settlement.
- The new internal writer must not be connected to production code or used as a release-ready settlement endpoint.

## Git Pull/Test

Focused: `php artisan test Modules/Finance/Tests/Feature/FinanceDebtInternalSettlementWriterTest.php`.

Full: `php artisan test Modules/Finance/Tests` with the same opt-in isolated MySQL test variables as CP26–29 (zero required SKIPs).

If either test fails, repair CP34 only. Even if both pass, this is **partial CP34**, not full settlement/reversal acceptance.

**Chưa đến CHECKPOINT TEST UI.**
