# Finance Phase 2 — CP31 Proposed Business Contract

**Status: PROPOSED — REQUIRES BUSINESS APPROVAL.** This is an acceptance-criteria proposal, not a decision to activate settlement. It describes existing service behavior separately from rules requiring explicit owner approval. No production migrations, UI changes, writer, settlement activation, PR or merge.

## Proposed acceptance matrix

| Area | Current observed behavior | Proposed acceptance criterion (not yet approved) |
| --- | --- | --- |
| Source ownership | A debt may link to an exact treasury source; manual debts may have no source. Source reader is diagnostic. | Each automated debt must have one canonical business document identity; prevent duplicate debt ownership for one invoice, define handling of manual debt separately. |
| Partial and multi-source allocation | Treasury voucher may allocate to multiple source documents; source inspection selects only exact matching allocation. | One settlement mapping must identify debt, voucher and exact source allocation, not total voucher amount; multiple partial payments must never exceed adjusted principal. |
| Currency and direction | Source/voucher readers validate matching currency and receivable→receipt, payable→payment. | Reject cross-currency and direction mismatch; decide whether FX conversion is out of scope. |
| Outstanding balance | Adjusted principal and treasury posted allocations are read-only projections; mapping balance is diagnostic. | Authoritative outstanding = adjusted principal minus approved net post/reversal mappings; never infer settlement solely from treasury allocation; reject negative outstanding and overpayment. |
| Reversal identity | Mapping schema has unique reversal origin; reversal diagnostics require same debt and exact amount. | Every reversal must reference exactly one original post, use a distinct voucher and remain append-only; prevent double reversal. |
| Lifecycle and cancellation | Draft/posted/reversed voucher status checks exist; atomic gate still rejects all execution. | Define permitted debt confirmation, voucher posting/reversal, source cancellation and invoice changes; forbid silent edits after settlement and specify compensating actions. |
| Permissions and audit | Server-side authorization tests exist for debt actions; no authorized settlement writer exists. | Explicit separate permissions for posting/reversal, immutable audit with actor/time/reason/idempotency key, enforced inside service. |

## Unresolved decisions — owner must approve before CP32/CP33

1. Is a **manual debt** ever eligible for settlement, and what authoritative document/allocation identifies its payment?
2. Can one source document be represented by multiple obligations (installments, splits), or exactly one? How are adjustments allocated?
3. For multi-source vouchers, what immutable allocation ID or equivalent ownership key must a settlement mapping store? Existing schema has only obligation/voucher/operation and reversal origin.
4. Are partial reversals supported? Current diagnostic contract requires full amount equality.
5. What happens when a posted source invoice is cancelled, adjusted, returned, or reissued after partial settlement?
6. Is multi-currency settlement forbidden, or is an approved FX-rate and rounding contract needed?
7. Which user roles can authorize post/reversal, and what audit metadata is mandatory?

These are **open questions**, not approved defaults. CP31 tests validate existing structural checks and deny execution; they do not implement or resolve these choices. Any CP32 mapping design depends on explicit answers.

## No settlement activation

`FinanceDebtSettlementTransactionProtocol::plan()` remains `ready=false`. `FinanceDebtAtomicSettlementGate::execute()` remains unconditionally fail-closed. The idempotency registry is not a settlement writer. CP26–CP29 isolated MySQL contention tests are not proof of full writer races. No production or UI testing at CP31.

## DELL WSL checkpoint

Focused: `php artisan test Modules/Finance/Tests/Feature/FinanceDebtBusinessContractAcceptanceTest.php`.

Full: `php artisan test Modules/Finance/Tests` with the opt-in isolated MySQL `finance_test` environment so CP26–29 do not SKIP. CP31 is pending until user reports both PASS. After PASS, discuss unresolved business decisions before any CP32 writer/schema work.
