# Inventory — Collaboration Handoff

## Final status

- Module: `Modules\Inventory`.
- Approved design inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS**.
- Batch C/C2 — Invoices Integration + Bulk Intake/Normalization: **MERGED / VERIFIED**.
- Batch D — Receiving + Product Matching + explicit Receipt confirmation: **MERGED / VERIFIED / UI PASS / FOCUSED REGRESSION PASS**.
- Cross-module structured lot/expiry + DRAFT editor enhancement: **MERGED via PR #182**.
- Current `main` checkpoint for this closeout: `8a41e22a08c0ee01cef038296b5680b5a8d1bb88`.
- Status: **CURRENT CHECKLIST COMPLETE / HANDOFF READY**.

## Canonical ownership boundary

```text
GDT acquisition / invoice persistence / canonical RAW / source annotations -> Modules\Invoices
Inventory staging / normalization / Inbox / matching / receiving / Receipt -> Modules\Inventory
Product / Pharma -> reference candidate sources only
```

Critical invariants:

```text
/admin/invoices/hoadon = only UI workflow allowed to acquire directly from GDT
Inventory never calls GDT
Inventory never parses storage/app/invoices/pdf
Invoice sync/import != stock posting
InventoryItem = canonical stock item master
Receipt DRAFT != stock
Receipt CONFIRMED -> immutable StockMovement -> StockBalance
```

## Canonical receiving flow

```text
Invoices canonical RAW
  -> Inventory Source Queue / Inbox
  -> eligibility gate
  -> line classification
  -> item matching / explicit item creation
  -> receiving review
  -> Receipt DRAFT
  -> optional DRAFT correction
  -> explicit Receipt confirmation
  -> immutable StockMovement
  -> StockBalance projection
```

Eligibility remains fail-safe: GOODS is eligible; SERVICE_EXPENSE is blocked; UNCLASSIFIED requires review; MIXED requires line-level decisions; STOCK lines without a canonical InventoryItem remain unresolved.

## Receiving Source Queue

Canonical route:

```text
GET /admin/inventory/invoice-inbox
admin.inventory.invoice-inbox
```

The queue supports year/month/search/status filtering and bounded pagination. A selected inbox opens as `/admin/inventory/invoice-inbox?inbox=<id>`.

### Refresh from source

The selected Inbox exposes **Cập nhật lại từ hóa đơn nguồn** when the Receipt is not `CONFIRMED` and the operator has `inventory.receipt.manage`.

Refresh reuses the canonical `InvoiceInventoryHandoffService` identity flow. It does not create duplicate Inbox records, does not post stock, preserves valid item mapping where applicable and refreshes source-controlled values such as lot/HSD. Confirmed receipts are locked from source refresh.

## Item matching and scalable search

`InventoryItem` remains the canonical warehouse item master. Product/Pharma candidates are reference-only.

Receiving supports explicit matching, explicit item creation, and correction of an item created specifically from the invoice line before stock confirmation. Shared InventoryItem master data is protected from accidental invoice-specific edits.

SKU/name selection uses `<x-search>` with bounded server-side lookup rather than an unbounded select. The dropdown stacking issue was fixed and accepted in UI testing.

## UOM / packaging safety

- same source/base UOM => conversion factor `1`;
- different UOM => explicit reviewed conversion factor required;
- silent factor `1` is rejected when UOMs differ;
- base quantity = source quantity × reviewed conversion factor;
- package metadata is descriptive master data and must not silently multiply invoice quantity.

## Lot / expiry / manufacture-date

Lot, expiry and manufacture date are receiving/lot-instance evidence, not InventoryItem identity fields.

Canonical source priority accepted by Inventory:

```text
structured GDT metadata
  -> legacy/top-level source fields
  -> deterministic text fallback
  -> null
```

Structured data wins over conflicting text; missing data is never fabricated. Manufacture date remains optional, and text such as `NSX: Việt Nam` is not treated as a manufacture date.

Accepted runtime evidence:

```text
CAMZITOL
structured LotNo = G0846
structured ExpiryDate = 2028-03-08
Inventory = G0846 / 2028-03-08

Tharodas invoice #287
source text = Lô: 020526, HSD: 04/05/2029
structured fields = null
normalizer deterministic-v4 = 020526 / 2029-05-04
Inventory after source refresh = 020526 / 2029-05-04
```

## Receipt DRAFT editor

The action **✎ Chỉnh sửa phiếu nhập nháp trước khi xác nhận** opens the dedicated DRAFT editor modal.

The modal supports searchable item change, source/base quantity and UOM review, conversion factor, lot/HSD/optional manufacture date, package metadata, and controlled master-data editing only for an InventoryItem created from that invoice line.

Saving refreshes the same DRAFT proposal through `InvoiceReceiptProposalService`. It remains non-posting. Confirmed receipts cannot use this correction path.

## Confirmation and idempotency

Confirmation goes only through canonical `ReceiptPostingService`; there is no invoice-specific direct stock-posting bypass.

Transaction safety, deterministic movement keys and database uniqueness prevent duplicate stock posting. Reconfirming a confirmed Receipt cannot increase stock twice.

Traceability remains:

```text
source invoice
  -> Inventory Inbox
  -> InventoryItem / receiving review
  -> Receipt / ReceiptLine
  -> StockMovement
  -> StockBalance
```

## Acceptance evidence

- Batch D focused regression: `33 passed / 207 assertions`.
- `InventoryInvoiceDraftEditorContractTest`: `3 passed / 25 assertions`.
- Structured lot/HSD mapping tests: PASS.
- Scoped Pint: PASS.
- Source Queue / receiving workspace: UI PASS.
- Source refresh: UI PASS.
- Searchable SKU dropdown: UI PASS.
- Receipt DRAFT editor: UI PASS.
- CAMZITOL structured lot/HSD: UI PASS.
- Tharodas #287 text-fallback lot/HSD: UI PASS.
- PR #182 merged into `main` at `8a41e22a`.
- Home machine verified synchronized: `main...origin/main` at `8a41e22a` before this documentation-only closeout.

## Checklist closeout

The implementation checklist covered by this chat is complete:

```text
core ledger                  DONE
admin dashboard/UI           DONE
invoice integration          DONE
bulk intake/normalization    DONE
receiving/product matching   DONE
DRAFT receipt review/edit    DONE
explicit stock confirmation  DONE
structured lot/HSD           DONE
text fallback lot/HSD        DONE
source refresh               DONE
scalable SKU search          DONE
focused tests/Pint           PASS
operator UI acceptance       PASS
implementation PR #182       MERGED
```

No implementation item from this chat remains open.

## Deferred / future scope

The following are not blockers for this closeout and require a separate approved scope if pursued:

- Excel audit/export and selected-vs-all-filtered export semantics;
- additional query/index/runtime hardening based on profiling;
- broader receiving automation while preserving explicit stock confirmation.

Future implementation starts from current `main`; do not continue work on the old Batch D or `fix/invoices-structured-lot-expiry` branches.
