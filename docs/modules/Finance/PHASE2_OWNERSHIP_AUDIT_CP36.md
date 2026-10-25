# CP36 — Ownership reconciliation and transaction rollback hardening

**Status: CODE COMMITTED / TWO-TIER TEST PENDING. NOT RELEASE READY.**

## Implemented

- `FinanceDebtSettlementOwnershipAudit::inspect(debtId)` is a read-only, always fail-closed diagnostic. Checks every post mapping against exact allocation ownership, original voucher, source and amount; verifies actor/reason on post ownership; detects orphan ownership, invalid reversal origins, reversal owning an allocation, and voucher reversal lifecycle mismatches. Uses the existing mapping balance invariant to detect overpayment and broken reversal amounts. No production write path is opened.
- Four new integration tests appended to `FinanceDebtInternalSettlementWriterTest`: post/reversal reconciliation, tampered allocation rejection, injected failure after post in an outer transaction, and injected failure after reversal in an outer transaction. Rollback tests check token, mapping and ownership counts.
- Existing writer is still limited to Laravel `testing` environment. Public atomic settlement gate remains unconditionally blocked.

## Not yet proven / release blockers

- **Two simultaneous MySQL connections against the actual writer are NOT tested in CP36.** Earlier CP26–29 scratch-table concurrency tests are not proof of actual writer race safety. This must be implemented and PASS before release.
- Authorization callback is not a production permission policy; reversal actor/reason are not persisted in a dedicated reversal audit table. An approved durable audit schema and permissions are required.
- Manual debt links, historical mapping backfill, treasury lifecycle integration and source cross-service lock ordering remain unresolved.
- No production migration, settlement activation, UI, PR, merge or deploy.

## DELL WSL checkpoint

`php artisan test Modules/Finance/Tests/Feature/FinanceDebtInternalSettlementWriterTest.php`

`php artisan test Modules/Finance/Tests`

Use the established isolated MySQL opt-in variables and check for unexpected skips. Repair CP36 failures before advancing. **Chưa đến CHECKPOINT TEST UI.**
