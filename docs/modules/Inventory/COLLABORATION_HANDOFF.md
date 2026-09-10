# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS** via PR #179, merge commit `df406fae`.
- Batch C branch: `feat/inventory-batch-c-invoice-integration`.
- Batch C — Invoices Integration + Product Matching + Draft Receipt: **IMPLEMENTED**.
- Batch C2 — Bulk Intake & Normalization: **IMPLEMENTED / HARDENING IN FINAL VERIFICATION**.
- C2-A parser-version-aware re-normalization: **REAL SMOKE PASS**.
- C2-B deterministic STOCK / NON_STOCK / UNRESOLVED classification: **IMPLEMENTED**.
- C2-C Product / Pharma reference candidate matching: **REAL SMOKE PASS**.
- Canonical GDT source ownership hardening: **IMPLEMENTED / LOCAL VERIFICATION PENDING**.
- Real Khang Phát invoice #261 pilot: **PASS** for normalization, publication idempotency, candidate safety and zero stock mutation.
- Previous focused pack before canonical-source hardening: **21 passed / 130 assertions**.
- Keep Batch C/C2 in one MR. Do not merge until the new focused pack, source-data smoke and final UI smoke pass.

## Canonical ownership boundary

```text
GDT external acquisition / invoice persistence / canonical RAW header+detail / source annotations -> Modules\Invoices
Inventory staging projection / normalization / inbox / matching / exception review / receipt proposal -> Modules\Inventory
Product / Pharma -> reference candidate sources only; never canonical stock owners
```

Critical invariants:

```text
/admin/invoices/hoadon = the only UI workflow allowed to acquire data directly from GDT
Inventory never calls GDT
Invoice sync/import != stock posting
```

`Modules\Invoices` now persists canonical source data in:

```text
invoice_source_records
```

The source record stores the full per-invoice GDT header payload, full GDT detail payload, hashes, fetched timestamps, source version, detail acquisition status/error and administrator business annotations.

`invoice_inventory_snapshots` and `invoice_inventory_staging_lines` are Inventory-facing projection/audit structures. They are not the canonical external source store. New normalization reads canonical RAW from Invoices and does not copy the full detail payload into the snapshot again; per-line RAW remains available on staging lines for traceability.

Inventory does not read `storage/app/invoices/pdf`, does not parse invoice PDF and does not update `Product.quantity`.

Physical stock still changes only through explicit Receipt confirmation and canonical Batch A posting services.

## Canonical GDT acquisition workflow

Canonical route:

```text
GET /admin/invoices/hoadon
admin.invoices.hoadon
```

The workflow is now:

```text
administrator selects range
  -> validate cached GDT token against GDT before bulk queue
  -> fetch complete invoice header list
  -> persist business invoice headers
  -> persist full per-invoice GDT RAW header
  -> reuse local RAW detail if already present
  -> fetch only missing GDT detail
  -> persist full GDT RAW detail
  -> retain hashes / timestamps / source version / acquisition status
  -> export/backup remains available
  -> downstream modules consume local persisted source data
```

A 401/403 during token validation or acquisition clears the cached token and stops the workflow rather than dispatching a large set of doomed Inventory staging jobs.

The previous worker optimization that skipped GDT solely because an Excel file existed was hardened. Existing Excel/Google Drive backup files do not prove canonical RAW completeness. GDT is skipped only when the local canonical source coverage for the requested range is complete. If Excel exists but RAW header/detail coverage is incomplete, `/admin/invoices/hoadon` continues acquisition to repair the canonical source store.

Acquisition is resumable: detail payloads already stored are reused; after interruption/token expiry, the next administrator sync only needs to acquire missing detail records.

## Source-data administration

Canonical route:

```text
GET /admin/invoices/source-data
admin.invoices.source-data
```

This route never calls GDT. It lets administrators inspect canonical source coverage/status and enrich source records with operational classification:

```text
UNCLASSIFIED
GOODS
SERVICE_EXPENSE
MIXED
```

Annotations include note, actor and timestamp. They can be scoped to one invoice or explicitly applied to all invoices from the same tax code.

When the administrator chooses “apply to the same tax code”, the record is marked with `classification_scope = SUPPLIER`. Existing invoices for that tax code/type are updated and future source records for the same supplier/type inherit the latest supplier rule. A later invoice-specific override uses `classification_scope = INVOICE` and does not replace the supplier rule for future invoices.

Source annotation is deterministic evidence, not a command to bypass line-level safety:

- `GOODS` can support STOCK when line evidence is otherwise weak;
- `SERVICE_EXPENSE` can support NON_STOCK when no physical-goods evidence conflicts;
- `SERVICE_EXPENSE` plus strong physical/GDT goods evidence fails safe to `UNRESOLVED`;
- `MIXED` never forces all lines to one class; line-level evidence decides;
- explicit GDT service/category/description evidence continues to win over supplier defaults;
- STOCK without a canonical `InventoryItem` remains `REVIEW_REQUIRED`.

## Inventory local-only intake

Canonical route:

```text
GET /admin/inventory/intake
admin.inventory.intake
```

The action is now explicitly **“Chuẩn hóa RAW đã lưu”**.

Flow:

```text
Invoices canonical RAW READY
  -> Inventory queues only purchase invoices with persisted READY detail
  -> deterministic local normalization
  -> publish Inventory Inbox
  -> deterministic classification/matching/reference candidates
  -> exception review
  -> READY
  -> Receipt DRAFT
  -> explicit operator confirmation later
  -> stock posting
```

Inventory does not perform token checks and does not call GDT. If no READY source data exists for the selected range, the UI points the administrator back to `/admin/invoices/hoadon`.

The 55 ERROR snapshots observed on 2026-09-10 were caused by an expired GDT token in the old Inventory-triggered detail acquisition path. They must not be deleted. After canonical RAW is acquired from Invoices, re-running local Inventory normalization reuses the existing snapshot identity and can recover those ERROR rows without another GDT call from Inventory.

## Deterministic normalization v3

`InvoiceLineNormalizer` parser marker:

```text
deterministic-v3
```

Rules include lot labels `Lô`, `Số lô`, `LOT`; expiry labels `HSD`, `HD`, `Hạn dùng`, `EXP`; full date and month/year support; light normalized-name cleanup; and preservation of source description separately.

Lot / manufacture date / expiry remain transaction/lot-instance evidence and are not InventoryItem master data.

Real Khang Phát invoice #261 remains the regression pilot:

```text
Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26001CN, HD: 28/03/2029
  -> 1880 lọ / lot 26001CN / expiry 2029-03-28

Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26002CN, HD: 30/03/2029
  -> 5880 lọ / lot 26002CN / expiry 2029-03-30
```

The two lines must never be merged.

## Product / Pharma candidate safeguards

Product and Pharma remain reference sources only. `InventoryItem` is the canonical stock owner.

Candidate discovery does not assign `inventory_item_id`. Unverified/demo Pharma records remain blocked from auto-match. The real #261 smoke produced only LOW-confidence demo references and left both stock lines `REVIEW_REQUIRED` with `inventory_item_id = null`.

## Receipt proposal safety

`InvoiceReceiptProposalService` is confirmed fail-safe:

- it selects only `classification = STOCK` lines;
- all-NON_STOCK invoices are rejected with “Hóa đơn không có dòng STOCK để tạo phiếu nhập.”;
- mixed invoices exclude NON_STOCK lines from the Receipt proposal;
- every STOCK line must already have InventoryItem, base quantity and base UOM;
- source lines are preserved individually, including lot/HSD;
- only a `DRAFT` receipt is created/refreshed;
- no `ReceiptPostingService`, confirmation, stock movement or balance mutation occurs in this proposal service.

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

Previous real publication smoke:

```text
published_count = 1
inbox_id = 1
processing_status = REVIEW_REQUIRED
receipt_id = null
line_count = 2
```

Zero-mutation proof:

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

No Receipt CONFIRMED was created and no stock posting was performed.

## Focused verification required after canonical-source hardening

Run only the directly impacted pack:

```text
tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php
tests/Feature/Inventory/InventoryBatchC2BulkPublicationContractTest.php
tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php
```

The updated tests lock:

- canonical GDT RAW source ownership;
- token preflight before bulk synchronization;
- local-only Inventory staging;
- source-data administration and supplier-rule inheritance;
- deterministic annotation safeguards;
- Product/Pharma reference-only behavior;
- all-NON_STOCK/mixed Receipt safety;
- no confirm/post-stock path.

No full regression is required by project policy.

## Remaining acceptance gate before merge

1. Pull latest branch and run the new Invoices migration.
2. Run the focused three-file test pack above.
3. Verify `/admin/invoices/hoadon` rejects an expired token before bulk queue.
4. Re-authenticate GDT and sync a small known purchase range first.
5. Verify `/admin/invoices/source-data` shows RAW header/detail coverage and annotation UI.
6. Verify supplier-wide and invoice-specific classification controls without calling GDT from the source-data route.
7. Run `/admin/inventory/intake` local normalization and verify it does not trigger GDT acquisition.
8. UI smoke desktop/tablet/mobile for Intake and Invoice Inbox.
9. Confirm Product/Pharma candidates remain reference-only and STOCK-unmapped lines remain review-required.
10. Do not confirm a Receipt or post stock during smoke.

## Deferred / next batches

- Explicit administrator force-refresh/version-history UX for intentionally reacquiring a previously complete GDT source record.
- Excel audit/export and selected-vs-all-filtered export semantics (Batch D).
- final query/index/runtime hardening.
- final module closeout after Batch C merge acceptance.

## Stop gate

Do not merge Batch C/C2 until the new focused tests pass, source-data + Inventory UI smoke is accepted by the operator, no stock mutation occurs before Receipt confirmation, final branch status is clean, and this handoff remains current.
