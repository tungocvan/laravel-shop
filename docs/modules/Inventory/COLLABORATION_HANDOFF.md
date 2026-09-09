# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS** via PR #179, merge commit `df406fae`.
- Batch C branch: `feat/inventory-batch-c-invoice-integration`.
- Batch C — Invoices Integration + Product Matching + Draft Receipt: **IMPLEMENTED / FOCUSED TESTS PASS**.
- Batch C2 — Bulk Intake & Normalization: **IMPLEMENTED / GATE 2–4 PASS**.
- Deterministic normalizer v3 real-data regression gate: **FOCUSED TESTS PASS**.
- Keep Batch C/C2 in one MR unless migration or UI smoke exposes a real blocker.

## Ownership boundary

```text
PDF / GDT acquisition / invoice persistence / raw detail snapshot / normalized source detail -> Modules\Invoices
Inventory invoice inbox / deterministic matching / exception review / receipt proposal / stock workflow -> Modules\Inventory
```

Critical invariant:

```text
Invoice sync/import != stock posting
```

Inventory does not read `storage/app/invoices/pdf`, does not parse invoice PDF and does not update `Product.quantity`.

Physical stock still changes only through the Batch A receipt confirmation path and `ReceiptPostingService` / canonical stock posting services.

## Batch C2 durable intake staging

Invoices owns:

```text
invoice_inventory_snapshots
invoice_inventory_staging_lines
```

Raw GDT payload and each source line remain durable audit evidence. `raw_description` preserves the source description exactly; normalization operates on a separate working value.

Lot / manufacture date / expiry date remain transaction/lot-instance evidence and are not copied into InventoryItem master data. Receipt proposal preserves source lines independently, so the same item on different invoice lines with different lot or expiry remains separate receipt lines.

## Deterministic normalization v3

`InvoiceLineNormalizer` uses parser marker:

```text
deterministic-v3
```

Rules:

- lot labels: `Lô`, `Số lô`, `LOT`;
- expiry labels: `HSD`, `HD`, `Hạn dùng` (plus existing EXP compatibility);
- expiry supports full day/month/year and month/year source formats;
- parsed expiry is persisted as a canonical date;
- `normalized_name` uses light cleanup only: transaction lot/expiry markers are removed, while strength and useful product/package wording are retained for matching;
- strength, dosage form, package specification and manufacturer remain best-effort metadata and are not normalization prerequisites;
- no fuzzy/AI matching is allowed to auto-confirm an InventoryItem.

Real Khang Phát invoice regression examples are protected:

```text
Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26001CN, HD: 28/03/2029
  -> lot_number 26001CN
  -> expiry_date 2029-03-28

Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26002CN, HD: 30/03/2029
  -> lot_number 26002CN
  -> expiry_date 2029-03-30
```

Focused verification reported PASS after v3:

```text
tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php
tests/Feature/Inventory/InventoryBatchC2BulkPublicationContractTest.php
```

No full regression was run, by design. No Receipt CONFIRMED was created and no stock posting was performed by this v3 change.

## Bulk intake / publication invariants

Canonical route:

```text
GET /admin/inventory/intake
admin.inventory.intake
```

Flow:

```text
Invoices local purchase headers
  -> bounded GDT detail staging
  -> RAW snapshot
  -> deterministic normalization
  -> publish Inventory Inbox
  -> deterministic matching / exception review
  -> READY
  -> Receipt DRAFT
  -> explicit operator confirmation later
  -> stock posting
```

`BulkInvoicePublicationService` accepts selected `NORMALIZED` snapshots, uses staged data, creates only DRAFT receipt proposals and never calls `ReceiptPostingService` or confirms stock.

Important: the current bulk dispatcher stages detail for purchase invoice headers already present in the local `invoices` table. Full remote acquisition of every 2026 invoice header is not claimed yet.

## Next acceptance gate

Do not run full-year 2026 intake yet.

Next use the real Khang Phát pilot to verify persisted v3 normalization and the DRAFT boundary:

1. pull the latest branch;
2. ensure the staging migration is present on the active database;
3. re-normalize/stage the selected Khang Phát invoice using v3;
4. inspect raw description, normalized name, lot and expiry values;
5. publish only the intended goods snapshot to Inventory Inbox;
6. resolve any UNRESOLVED matching through the canonical review flow;
7. create Receipt DRAFT only;
8. verify separate lot/expiry source lines remain separate receipt lines;
9. verify stock movements and balances remain unchanged before explicit receipt confirmation;
10. run desktop/tablet/mobile UI smoke for intake, inbox and receipt draft.

## Deferred / next batches

- Parser-version-aware re-normalization from persisted raw staging data without requiring GDT refetch/deletion of the snapshot.
- Inventory-level stock/non-stock eligibility classification before large-scale publication.
- Full 2026 remote invoice-header acquisition strategy if local `invoices` is not already complete.
- Excel audit/export and selected-vs-all-filtered export semantics (Batch D).
- final query/index/runtime hardening.
- final module closeout.

## Stop gate

Do not merge Batch C/C2 until the real-data pilot and UI smoke are accepted, no stock mutation occurs before receipt confirmation, and final branch status is clean.
