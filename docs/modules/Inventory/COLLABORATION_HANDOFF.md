# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS** via PR #179, merge commit `df406fae`.
- Batch C branch: `feat/inventory-batch-c-invoice-integration`.
- Batch C — Invoices Integration + Product Matching + Draft Receipt: **IMPLEMENTED / FOCUSED TESTS PASS**.
- Batch C2 — Bulk Intake & Normalization: **IMPLEMENTED / HARDENING PASS**.
- C2-A parser-version-aware RAW re-normalization: **IMPLEMENTED / REAL SMOKE PASS**.
- C2-B deterministic STOCK / NON_STOCK / UNRESOLVED classification: **IMPLEMENTED / FOCUSED TESTS PASS**.
- C2-C Product / Pharma reference candidate matching: **IMPLEMENTED / FOCUSED TESTS + REAL SMOKE PASS**.
- Real Khang Phát invoice #261 pilot: **PASS** for normalization, publication idempotency, candidate safety and zero stock mutation.
- Latest focused pack reported: **21 passed / 130 assertions**.
- Keep Batch C/C2 in one MR. Do not merge until UI smoke and final operator acceptance are complete.

## Ownership boundary

```text
PDF / GDT acquisition / invoice persistence / raw detail snapshot / normalized source detail -> Modules\Invoices
Inventory invoice inbox / deterministic classification / deterministic matching / exception review / receipt proposal / stock workflow -> Modules\Inventory
Product / Pharma -> reference candidate sources only; never canonical stock owners
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
  -> normalized_name Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU)
  -> quantity 1880 lọ
  -> lot_number 26001CN
  -> expiry_date 2029-03-28

Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26002CN, HD: 30/03/2029
  -> normalized_name Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU)
  -> quantity 5880 lọ
  -> lot_number 26002CN
  -> expiry_date 2029-03-30
```

## C2-A — parser-version-aware RAW re-normalization

`invoice_inventory_snapshots.normalizer_version` tracks the normalizer/parser version used to produce staged normalized lines.

Required behavior is implemented:

```text
same raw payload + same normalizer version -> no-op
same raw payload + newer/different normalizer version -> re-normalize from persisted RAW snapshot
```

When the persisted RAW snapshot is usable, re-normalization does not fetch GDT again, does not delete the snapshot and does not duplicate staging lines.

Real snapshot #4 smoke after upgrading legacy `normalizer_version = null`:

```text
snapshot_id = 4
status = NORMALIZED
normalizer_version = deterministic-v3
attempt_count_before = 1
attempt_count_after = 1
attempt_count_delta = 0
fetched_at_before = 2026-09-09 18:18:34
fetched_at_after  = 2026-09-09 18:18:34
line_count = 2
```

This proves the upgrade used persisted RAW data rather than a new GDT fetch.

## C2-B — deterministic stock eligibility classification

Inventory classifies invoice lines before canonical item matching using a deterministic, fail-safe classifier:

```text
STOCK
NON_STOCK
UNRESOLVED
```

Rules include:

- GDT `HH` / `Hàng hóa` is strong STOCK evidence;
- GDT service category is NON_STOCK;
- deterministic description markers such as service, interest / lãi, fee / phí, freight / cước and `Thanh toan lai` are NON_STOCK;
- physical goods UOM can support STOCK classification;
- insufficient evidence remains UNRESOLVED;
- no supplier-specific hardcoding and no fuzzy/AI auto-confirmation.

Important readiness gate:

```text
classification = STOCK + inventory_item_id = null -> REVIEW_REQUIRED
```

A line being classified as STOCK does not make an invoice READY until the canonical InventoryItem has been deterministically or manually resolved.

## C2-C — Product / Pharma reference candidates

Product and Pharma are reference candidate sources only. `InventoryItem` remains the canonical stock owner.

Candidate evidence is stored under line metadata for audit/review and may be shown in the Invoice Inbox UI. Candidate discovery does not assign `inventory_item_id`.

Pharma safeguards:

- active ingredient alone is never enough for auto-match;
- strength/concentration, unit, dosage form and package specification are deterministic evidence inputs;
- `identity_status = unverified` blocks auto-match;
- `DEMO-*` registration numbers block auto-match;
- demo notes/records block auto-match;
- Product/Pharma reference candidates remain `auto_match_eligible = false` until a future explicit canonical-link contract exists.

Real invoice #261 candidate smoke produced only two Pharma demo references:

```text
Cefuroxime Demo · 500 mg · Viên nén · Viên
Cefuroxime Supplier Demo · 500 mg · Viên · Viên
```

Both were LOW confidence with unit/strength mismatch, `identity_status = unverified`, `DEMO-*` registration and explicit `reference_only_no_inventory_item_link` blocking evidence. Neither assigned an InventoryItem.

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
  -> deterministic stock eligibility classification
  -> deterministic InventoryItem matching / reference candidates / exception review
  -> READY
  -> Receipt DRAFT
  -> explicit operator confirmation later
  -> stock posting
```

`BulkInvoicePublicationService` accepts selected `NORMALIZED` snapshots and uses staged data. Publication to Inbox does not create stock movements or balances and does not confirm receipts.

`createDraftReceipts()` may create only DRAFT receipt proposals after the Inbox is READY; it never calls `ReceiptPostingService` or confirms stock.

Important: the current bulk dispatcher stages detail for purchase invoice headers already present in the local `invoices` table. Full remote acquisition of every 2026 invoice header is not claimed yet.

## Real invoice #261 acceptance evidence

Supplier:

```text
CÔNG TY TNHH THƯƠNG MẠI DƯỢC PHẨM KHANG PHÁT
MST 0317953611
invoice_number 261
symbol 1/C26TKP
local invoice id 2599
source identity gdt:purchase:lookup:46FAI2PAR67B
snapshot id 4
```

Real publication smoke after C2-A/B/C hardening:

```text
published_count = 1
inbox_id = 1
processing_status = REVIEW_REQUIRED
receipt_id = null
line_count = 2
```

Both lines remain STOCK evidence but unresolved canonically:

```text
line 1: 1880 lọ / lot 26001CN / HSD 2029-03-28 / inventory_item_id null
line 2: 5880 lọ / lot 26002CN / HSD 2029-03-30 / inventory_item_id null
```

Idempotency / zero-mutation proof on re-publication:

```text
before balances = 0
before movements = 0
before receipts = 0
before inbox_lines = 2

after balances = 0
after movements = 0
after receipts = 0
after inbox_lines = 2

delta balances = 0
delta movements = 0
delta receipts = 0
delta inbox_lines = 0
```

No Receipt CONFIRMED was created and no stock posting was performed during this pilot.

## Focused verification

Latest focused pack reported PASS:

```text
tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php
tests/Feature/Inventory/InventoryBatchC2BulkPublicationContractTest.php
tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php

21 passed / 130 assertions
```

No full regression was run, by design.

## Remaining acceptance gate before merge

Code hardening and real-data backend smoke for Batch C/C2 are complete.

Before merge, perform UI smoke on the current branch for:

1. `/admin/inventory/intake` desktop/tablet/mobile;
2. Inventory Invoice Inbox desktop/tablet/mobile;
3. Product/Pharma candidate evidence is readable and does not imply auto-match;
4. unresolved STOCK lines remain visibly review-required;
5. NON_STOCK action remains available;
6. Receipt DRAFT action is available only when READY;
7. no confirmation/post-stock action exists in the intake/inbox workflow;
8. verify Inventory navigation/menu exposes the intake route appropriately.

Do not create or confirm a stock-posting receipt as part of this UI smoke.

## Deferred / next batches

- Full 2026 remote invoice-header acquisition strategy if local `invoices` is not already complete.
- Excel audit/export and selected-vs-all-filtered export semantics (Batch D).
- final query/index/runtime hardening.
- final module closeout after Batch C merge acceptance.

## Stop gate

Do not merge Batch C/C2 until UI smoke is accepted by the operator, no stock mutation occurs before receipt confirmation, the final branch status is clean, and this handoff remains current.
