# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS**.
- Batch C/C2 — Invoices Integration + Bulk Intake/Normalization: **MERGED / VERIFIED**.
- Batch D branch: `feat/inventory-batch-d-receiving-product-matching`.
- Batch D — Receiving + Product Matching + explicit Receipt confirmation: **IMPLEMENTED / UI PASS / FOCUSED REGRESSION PASS / READY FOR PR**.
- Cross-module canonical lot/expiry fix branch: `fix/invoices-structured-lot-expiry`.
- Cross-module operator acceptance on 2026-09-11: **UI PASS** for Source Data lot/HSD, generated PDF, Inventory source refresh, item search, DRAFT editor and text-fallback lot/HSD propagation.
- Structured CAMZITOL acceptance: `G0846 / 2028-03-08`.
- Text-fallback Tharodas invoice #287 acceptance: `020526 / 2029-05-04`.
- Focused regression on 2026-09-11: **33 passed / 207 assertions** for Batch D baseline; additional cross-module focused tests and Pint were run on `fix/invoices-structured-lot-expiry` and passed after fixes.

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

## Source refresh for existing Inbox rows

The selected Inbox page exposes **Cập nhật lại từ hóa đơn nguồn** while the Receipt is not `CONFIRMED` and the operator has `inventory.receipt.manage`.

The refresh path reuses the canonical `InvoiceInventoryHandoffService` / `InventoryInvoiceIntegrationService` identity flow. It does not create a duplicate Inbox. Existing valid item mapping is preserved, while source-controlled fields such as lot, expiry and manufacture date can be refreshed from the latest Invoices contract.

Safety rules:

- no refresh write is allowed after Receipt confirmation;
- refresh does not post stock;
- when a Receipt already exists as `DRAFT`, refreshed Inbox data remains reviewable and the operator explicitly refreshes the DRAFT proposal before confirmation;
- confirmed stock history is never rewritten from refreshed invoice source data.

## Item matching and master-data safety

`InventoryItem` is the canonical warehouse item master. Product/Pharma candidates are reference-only and cannot silently assign stock ownership.

The receiving workspace supports explicit matching to an existing InventoryItem, explicit item creation from an invoice line, revision of an item created specifically from that invoice line before stock confirmation, visible SKU/base UOM, lot/expiry tracking flags, optional fractional quantity and package metadata.

Item matching now uses a searchable SKU/name experience rather than depending on a fixed large select list. The search is bounded server-side and suitable for a growing SKU catalog. The search UI was accepted after fixing dropdown stacking so results render above adjacent receiving cards.

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

Cross-module source priority now accepted by Inventory is:

```text
structured GDT metadata
  -> legacy/top-level source fields
  -> deterministic text fallback from invoice description
```

Structured values always win over conflicting text. Text fallback remains valid when the source has no structured lot/HSD fields.

Accepted evidence:

```text
CAMZITOL
structured LotNo = G0846
structured ExpiryDate = 2028-03-08
Inventory = G0846 / 2028-03-08

Tharodas invoice #287
source description contains: Lô: 020526, HSD: 04/05/2029
structured LotNo/ExpiryDate = null
normalizer deterministic-v4 = 020526 / 2029-05-04
Inventory after source refresh = 020526 / 2029-05-04
```

This confirms both structured and text-fallback paths reach Inventory Inbox without requiring OCR, generated-PDF parsing or manual re-entry.

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

The operator action **✎ Chỉnh sửa phiếu nhập nháp trước khi xác nhận** now opens a dedicated DRAFT editor modal rather than only expanding an inline details block.

The modal supports:

- searchable item change by SKU/name;
- source/base quantity and UOM review;
- conversion-factor correction;
- lot / HSD / optional manufacture-date correction;
- package metadata review/update;
- SKU/name/base-UOM/master-data editing only when the InventoryItem was explicitly created from that invoice line;
- protection of shared InventoryItem master data from accidental invoice-specific edits.

`Cập nhật phiếu nhập nháp` refreshes the same DRAFT proposal through `InvoiceReceiptProposalService`. This remains a non-posting operation. A confirmed Receipt is blocked from this correction path.

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

## Accepted UI / regression evidence

Operator UAT: **UI PASS**.

Accepted cross-module flow:

```text
Invoices Source Data
  -> lot/HSD visible in source detail
  -> generated PDF reflects canonical source fields
  -> Inventory source refresh
  -> Inbox receives structured or text-fallback lot/HSD
  -> searchable SKU matching
  -> Receipt DRAFT
  -> modal correction before confirmation
  -> explicit confirmation only when operator approves
```

Focused evidence includes:

```text
StructuredLotExpiryMappingTest: PASS
InventoryInvoiceDraftEditorContractTest: 3 passed / 25 assertions
Pint for draft editor/controller/routes/test: PASS
Inventory UI source refresh: PASS
Inventory searchable SKU dropdown: UI PASS after stacking fix
Receipt DRAFT modal: UI PASS
Invoice #287 Tharodas lot/HSD refresh: UI PASS
```

The UI retains bounded pagination `10 / 25 / 50 / 100`, visible form controls, loading/disabled mutation states and responsive desktop/tablet/mobile behavior.

## PR / merge gate

Cross-module branch `fix/invoices-structured-lot-expiry` is in closeout state with operator UI acceptance recorded.

Before merge:

- keep Invoices and Inventory handoff docs synchronized;
- run any final focused regression requested by PR/CI;
- do not merge until explicit operator confirmation.

Merge remains a separate final action. No automatic merge is authorized by this handoff.

## Deferred / next work

- Excel audit/export and selected-vs-all-filtered export semantics unless separately approved for a later batch.
- Additional query/index/runtime hardening when profiling demonstrates need.
- Broader receiving automation only if it preserves explicit stock confirmation and ownership boundaries.
