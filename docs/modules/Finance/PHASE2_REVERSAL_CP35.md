# CP35 — Internal full reversal, testing only

Status: **CODE COMMITTED, AWAITING TWO-TIER TEST — NOT PRODUCTION READY**.

## Implemented

`FinanceDebtInternalSettlementWriter::reverse` now implements an internal **full** reversal in Laravel's `testing` environment only. It requires explicit actor ID, reason and authorizer callback; verifies original post mapping, exact allocation ownership, original voucher ID and `reversed` lifecycle, currency and direction, exact allocation amount, debt source identity, non-duplicate reversal and consistent diagnostic mapping balance. The transaction locks debt, voucher, original mapping and allocation; reserves reversal idempotency and appends a reversal mapping atomically. Existing allocation ownership is retained as immutable historical evidence, never reassigned. Database unique indexes enforce one reversal per original mapping and scoped token uniqueness.

Added integration tests for successful reversal, duplicate reversal rejection without a second token, and lifecycle rejection. Existing CP34 tests remain. Public `FinanceDebtAtomicSettlementGate` is still **unconditionally fail-closed**; no routes, production activation, PR, merge or deploy.

## Explicit remaining blockers

- No production-grade policy/role integration: the callback only serves the test boundary.
- No demonstrated two-connection concurrency or injected mid-transaction failure test for this new reversal writer yet. Existing CP26–29 concurrency tests exercise earlier guard primitives, not this writer.
- Manual debts still lack explicit payment links; legacy mappings are not migrated into authoritative ownership.
- Treasury reversal lifecycle integration, cross-service lock-order/deadlock analysis, cancellation audit and authorization semantics require business review.
- Migration CP34 is additive and **must not be applied to production**.
- Do not represent passing this checkpoint as settlement production readiness.

## Test commands

`php artisan test Modules/Finance/Tests/Feature/FinanceDebtInternalSettlementWriterTest.php`

`php artisan test Modules/Finance/Tests`

Use isolated MySQL opt-in settings for full suite, check for zero required SKIPs. If failed, repair CP35 only.

**Chưa đến CHECKPOINT TEST UI.**
