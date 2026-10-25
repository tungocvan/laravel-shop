# CP37 — Durable internal settlement/reversal audit

**Status: COMMITTED / TWO-TIER TEST PENDING — NOT PRODUCTION READY.**

## Delivered

- Additive migration `2026_10_24_000005_create_finance_debt_settlement_audit_events_table.php`: immutable-intent event rows, unique mapping ownership, obligation/voucher, operation, original mapping, actor, reason, idempotency key and occurrence timestamp.
- `FinanceDebtInternalSettlementWriter` writes audit row inside the **same transaction** as idempotency, settlement/reversal mapping and allocation ownership. A missing audit table denies the write.
- `FinanceDebtSettlementOwnershipAudit` verifies matching actor, reason, voucher, mapping, operation and reversal origin whenever the audit table is present. Read-only and `eligible=false`.
- Integration tests for audit actor/origin/idempotency, denied authorization, and audit rollback with both operations.

## Explicit limitations

- **Actual two-connection concurrent invocation of this writer has NOT been demonstrated**. Earlier CP26–29 isolated concurrency tests cover other primitives. Do not claim this requirement is satisfied.
- Callback authorization remains testing-only and is **not a production permission policy**. An arbitrary callback returning true is not acceptable as a real access-control boundary.
- Audit is append-only by writer convention, not enforced against arbitrary DB updates/deletes; historical data/backfill and tamper-resistant audit controls are outstanding.
- Manual-debt explicit payment linkage, treasury lifecycle coordination and global lock-order verification are unresolved.
- Existing public settlement gate remains unconditionally fail-closed. Do not run production migrations, deploy, merge or open UI.

## DELL WSL test

Focused: `php artisan test Modules/Finance/Tests/Feature/FinanceDebtInternalSettlementWriterTest.php`

Full: `php artisan test Modules/Finance/Tests` using isolated MySQL opt-in. Report FAIL/SKIP details. No tests were executed by assistant.

**Chưa đến CHECKPOINT TEST UI.**
