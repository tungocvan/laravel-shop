# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS**.
- Batch C/C2 — Invoices Integration + Bulk Intake/Normalization: **MERGED / VERIFIED**.
- Batch D branch: `feat/inventory-batch-d-receiving-product-matching`.
- Batch D — Receiving + Product Matching + explicit Receipt confirmation: **IMPLEMENTED / UI PASS / FOCUSED REGRESSION PENDING**.
- Operator UI acceptance: **UI PASS** on 2026-09-11 for source queue, receiving workspace, item/lot review, DRAFT receipt review and DRAFT correction before confirmation.
- Do not merge Batch D until the focused regression pack passes and final branch status is clean.

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

The receiving workspace supports:

- explicit matching to an existing InventoryItem;
- explicit standalone InventoryItem creation from an invoice line;
- revision of an item created specifically from that invoice line before stock confirmation;
- visible SKU and base UOM;
- lot-tracking / expiry-tracking flags;
- optional fractional quantity support;
- package metadata such as `1 Hộp = 60 Viên` without using that metadata as a stock conversion unless the invoice UOM actually requires conversion.

Items created from invoice receiving but not yet confirmed are displayed in the item catalog as **Chờ xác nhận nhập kho**, with no false implication that stock already exists.

## UOM conversion safety

Receiving conversion is explicit and fail-safe:

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

If source UOM were `Hộp` and base UOM were `Viên`, an explicit conversion factor would be required.

## Lot / expiry / manufacture-date review

Lot number, manufacture date and expiry date are receiving/lot-instance evidence, not InventoryItem master fields.

The receiving UI pre-fills them when normalized invoice evidence exists and allows explicit operator review. Manufacture date is optional. Text such as `NSX: Việt Nam` is not interpreted as a manufacture date.

Tracking rules remain enforced: a lot-tracked item requires a lot number and an expiry-tracked item requires expiry before the DRAFT can be prepared.

## Receipt DRAFT review and correction

Batch D now uses the following UI progression:

```text
Bước 1 — Kiểm tra hóa đơn
Bước 2 — Đối chiếu hàng
Bước 3 — Thông tin nhập
Bước 4 — Kiểm tra phiếu
Bước 5 — Xác nhận
```

A Receipt in `DRAFT` is **Bước 4**, not Bước 5. Only a `CONFIRMED` Receipt reaches Bước 5.

The DRAFT review modal shows the source invoice, supplier, receiving warehouse, item/SKU, source and base quantities/UOMs, conversion factor where applicable, lot, expiry, manufacture date and package metadata.

The operator can choose **Quay lại chỉnh sửa** before confirmation. While the Receipt remains `DRAFT`, the workspace allows correction of receiving warehouse and receiving review fields, then **Cập nhật phiếu nhập nháp** refreshes the same DRAFT proposal. It does not create a new confirmed transaction and does not mutate stock.

This correction path is deliberately unavailable after confirmation. Confirmed documents remain protected/immutable according to the core ledger invariants.

## Canonical confirmation and idempotency

The receiving workspace confirms only through the canonical `ReceiptPostingService`.

It does not call `StockPostingService` directly and does not implement an invoice-specific stock posting path.

Confirmation is transaction-safe and preserves the Batch A invariants: document/line locking, active warehouse/item validation, positive quantity checks, UOM validation, deterministic movement keys and database uniqueness. Reconfirming the same confirmed Receipt does not add stock a second time.

Traceability remains:

```text
source invoice
  -> Inventory Inbox
  -> matched InventoryItem / receiving review
  -> Receipt
  -> ReceiptLine
  -> StockMovement
  -> StockBalance
```

## Inventory item catalog lifecycle

The admin item catalog distinguishes business lifecycle from the simple active/inactive master flag.

Relevant states include:

- `Chờ xác nhận nhập kho` for invoice-created items whose receiving transaction is still pending;
- in-stock/zero-stock/catalog states based on actual ledger/balance evidence;
- inactive when the master item is disabled.

For pending invoice-created items, the catalog links back to the corresponding receiving inbox. Stock is shown as **Chưa ghi sổ** rather than pretending the DRAFT quantity is available inventory.

Confirmed items expose actual balance and lot/HSD evidence from warehouse data.

## Batch D UI acceptance

Operator UAT result on 2026-09-11: **UI PASS**.

Accepted flow includes:

```text
Source Queue
  -> open invoice receiving workspace
  -> review/match item
  -> review quantity/UOM/conversion/lot/HSD
  -> create Receipt DRAFT
  -> Step 4 review modal
  -> return to correct DRAFT when necessary
  -> refresh same DRAFT
  -> review again
  -> explicit confirmation
```

The UI follows the Inventory admin form/pagination boundaries, including bounded page sizes `10 / 25 / 50 / 100`, visible inputs, loading/disabled mutation states and responsive desktop/tablet/mobile layout.

## Focused verification required before Batch D merge

Run only the directly impacted pack:

```text
tests/Feature/Inventory/InventoryBatchDDraftReceiptCorrectionContractTest.php
tests/Feature/Inventory/InventoryBatchDReceivingUiContractTest.php
tests/Feature/Inventory/InventoryBatchDEndToEndReceivingContractTest.php
tests/Feature/Inventory/InventoryBatchCInvoiceIntegrationContractTest.php
tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php
```

Command:

```bash
php artisan test \
  tests/Feature/Inventory/InventoryBatchDDraftReceiptCorrectionContractTest.php \
  tests/Feature/Inventory/InventoryBatchDReceivingUiContractTest.php \
  tests/Feature/Inventory/InventoryBatchDEndToEndReceivingContractTest.php \
  tests/Feature/Inventory/InventoryBatchCInvoiceIntegrationContractTest.php \
  tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php
```

The pack must retain evidence that:

- DRAFT correction is allowed only before confirmation;
- explicit DRAFT refresh is intentional and stock-safe;
- UI review/correction anchors remain present;
- confirmation goes only through `ReceiptPostingService`;
- no direct `StockPostingService` bypass exists in the Inbox component;
- Invoices/Inventory integration contract remains compatible;
- C2 exception review remains compatible.

## Remaining acceptance gate before merge

1. Pull the latest Batch D branch.
2. Run the focused five-file test pack above.
3. Confirm the focused pack is PASS.
4. UI gate is already **PASS**; repeat UI smoke only if a subsequent code change touches receiving UI behavior.
5. Verify final branch/working-tree status is clean and synchronized.
6. Keep this handoff current before PR/merge.
7. Merge only after the operator accepts the final gate.

## Deferred / next work

- Excel audit/export and selected-vs-all-filtered export semantics unless separately included in a later approved batch.
- Additional query/index/runtime hardening when profiling demonstrates need.
- Broader receiving automation only if it preserves explicit stock confirmation and ownership boundaries.

## Stop gate

Do not merge Batch D until the focused regression pack passes, the branch is clean/synchronized, UI PASS remains valid, stock still changes only through explicit canonical Receipt confirmation, and this handoff remains current.
