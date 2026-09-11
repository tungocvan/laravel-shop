# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS**.
- Batch C/C2 — Invoices Integration + Bulk Intake/Normalization: **MERGED / VERIFIED**.
- Batch D branch: `feat/inventory-batch-d-receiving-product-matching`.
- Batch D — Receiving + Product Matching + explicit Receipt confirmation: **IMPLEMENTED / UI PASS / FOCUSED REGRESSION PASS / READY FOR PR**.
- Operator UI acceptance: **UI PASS** on 2026-09-11 for source queue, receiving workspace, item/lot review, DRAFT receipt review and DRAFT correction before confirmation.
- Focused regression on 2026-09-11: **33 passed / 207 assertions**.
- Final operator branch status after regression: `feat/inventory-batch-d-receiving-product-matching...origin/feat/inventory-batch-d-receiving-product-matching` with no ahead/behind marker.

## Canonical ownership boundary

```text
GDT external acquisition / invoice persistence / canonical RAW header+detail / source annotations -> Modules\Invoices
Inventory staging projection / normalization / inbox / matching / receiving review / Receipt DRAFT -> Modules\Inventory
Product / Pharma -> reference candidate sources only; never canonical stock owners
```

Critical invariants:

```text
/admin/invoices/hoadon = the only UI workflow allowed to acquire data directly from GDT
Inventory never calls GDT
Invoice sync/import != stock posting
InventoryItem = canonical stock item master
Receipt DRAFT != stock
Receipt CONFIRMED -> canonical posting -> immutable Movement -> Balance
```

Inventory does not read `storage/app/invoices/pdf`, does not parse invoice PDF and does not update `Product.quantity`.

## Batch D canonical receiving flow

```text
Invoices canonical RAW
  -> Inventory Source Queue / Inbox
  -> eligibility gate
  -> line classification
  -> item matching / explicit item creation
  -> receiving review
  -> Receipt DRAFT
  -> Step 4: operator reviews and may correct the DRAFT
  -> explicit Receipt confirmation
  -> immutable StockMovement
  -> StockBalance projection
```

Eligibility remains fail-safe:

- `GOODS` is eligible for receiving review.
- `SERVICE_EXPENSE` is blocked from stock receiving.
- `UNCLASSIFIED` is blocked until reviewed.
- `MIXED` requires line-level decisions and is never wholesale-posted to stock.
- STOCK lines without a canonical `InventoryItem` remain unresolved/review-required.

## Receiving Source Queue

Canonical route:

```text
GET /admin/inventory/invoice-inbox
admin.inventory.invoice-inbox
```

Without an `inbox` query parameter, the page is the receiving Source Queue. It supports year/month/search/status filtering and stable status counts. The queue distinguishes ready, needs review, needs RAW, in progress and confirmed states.

Starting/continuing receiving hands off through the versioned Invoices -> Inventory boundary and opens the selected inbox as:

```text
/admin/inventory/invoice-inbox?inbox=<id>
```

## Item matching and master-data safety

`InventoryItem` is the canonical warehouse item master. Product/Pharma candidates are reference-only and cannot silently assign stock ownership.

The receiving workspace supports explicit matching to an existing InventoryItem, explicit item creation from an invoice line, revision of an item created specifically from that invoice line before stock confirmation, visible SKU/base UOM, lot/expiry tracking flags, optional fractional quantity and package metadata.

Items created from invoice receiving but not yet confirmed are displayed in the item catalog as **Chờ xác nhận nhập kho**. Stock is shown as **Chưa ghi sổ** until canonical Receipt confirmation creates ledger evidence.

## UOM conversion safety

- when source UOM equals base UOM, factor is 1;
- when source UOM differs from base UOM, an explicit reviewed conversion factor is required;
- silent factor `1` is rejected when UOMs differ;
- base quantity is derived from source quantity × reviewed conversion factor;
- package metadata is descriptive master data and must not silently multiply invoice quantity.

Example:

```text
Invoice source = 3,060 Viên
Inventory base = Viên
Package metadata = 1 Hộp = 60 Viên
Receiving quantity remains 3,060 Viên.
```

## Lot / expiry / manufacture-date review

Lot number, manufacture date and expiry date remain receiving/lot-instance evidence, not InventoryItem master fields.

The UI pre-fills them when normalized invoice evidence exists and allows explicit operator review. Manufacture date is optional. Text such as `NSX: Việt Nam` is not interpreted as a manufacture date.

Tracking rules remain enforced: a lot-tracked item requires a lot number and an expiry-tracked item requires expiry before the DRAFT can be prepared.

## Receipt DRAFT review and correction

Batch D UI progression:

```text
Bước 1 — Kiểm tra hóa đơn
Bước 2 — Đối chiếu hàng
Bước 3 — Thông tin nhập
Bước 4 — Kiểm tra phiếu
Bước 5 — Xác nhận
```

A Receipt in `DRAFT` is **Bước 4**. Only a `CONFIRMED` Receipt reaches Bước 5.

The DRAFT review modal shows the source invoice, supplier, receiving warehouse, item/SKU, source/base quantities/UOMs, conversion factor, lot, expiry, manufacture date and package metadata where available.

The operator can use **Quay lại chỉnh sửa**. While the Receipt remains `DRAFT`, the workspace allows correction of warehouse and receiving review fields, then **Cập nhật phiếu nhập nháp** refreshes the same DRAFT proposal. This explicit refresh replaces only DRAFT lines and does not mutate stock. A confirmed Receipt is blocked from this correction path.

## Canonical confirmation and idempotency

The receiving workspace confirms only through canonical `ReceiptPostingService`.

It does not call `StockPostingService` directly and does not implement an invoice-specific stock posting path.

Confirmation remains transaction-safe and preserves the Batch A invariants: locking, active warehouse/item validation, positive quantity checks, UOM validation, deterministic movement keys and database uniqueness. Reconfirming a confirmed Receipt cannot add stock twice.

Traceability:

```text
source invoice
  -> Inventory Inbox
  -> matched InventoryItem / receiving review
  -> Receipt
  -> ReceiptLine
  -> StockMovement
  -> StockBalance
```

## Accepted Batch D UI

Operator UAT: **UI PASS**.

Accepted flow:

```text
Source Queue
  -> receiving workspace
  -> match/create item
  -> review quantity/UOM/conversion/lot/HSD
  -> create Receipt DRAFT
  -> Step 4 review
  -> optionally return and correct DRAFT
  -> refresh same DRAFT
  -> review again
  -> explicit confirmation
```

The UI retains bounded pagination `10 / 25 / 50 / 100`, visible form controls, loading/disabled mutation states and responsive desktop/tablet/mobile behavior.

## Final focused regression

Executed pack:

```text
tests/Feature/Inventory/InventoryBatchDDraftReceiptCorrectionContractTest.php
tests/Feature/Inventory/InventoryBatchDReceivingUiContractTest.php
tests/Feature/Inventory/InventoryBatchDEndToEndReceivingContractTest.php
tests/Feature/Inventory/InventoryBatchCInvoiceIntegrationContractTest.php
tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php
```

Result:

```text
Tests: 33 passed (207 assertions)
Duration: 5.11s
```

The regression confirms DRAFT correction safety, intentional DRAFT refresh, receiving UI contracts, canonical `ReceiptPostingService` confirmation, no direct posting bypass, Invoices/Inventory integration compatibility and C2 exception-review compatibility.

## PR / merge gate

All pre-PR gates are now satisfied:

- implementation complete;
- UI PASS;
- focused regression PASS (`33 / 207`);
- branch synchronized with origin;
- handoff current.

Batch D is ready to be opened as one coherent PR against `main`.

Merge remains a separate final action. After PR review/CI, merge only if no new change invalidates the UI/test evidence above.

## Deferred / next work

- Excel audit/export and selected-vs-all-filtered export semantics unless separately approved for a later batch.
- Additional query/index/runtime hardening when profiling demonstrates need.
- Broader receiving automation only if it preserves explicit stock confirmation and ownership boundaries.
