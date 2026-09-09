# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS** via PR #179, merge commit `df406fae`.
- Batch C branch: `feat/inventory-batch-c-invoice-integration`.
- Batch C — Invoices Integration + Product Matching + Draft Receipt: **IMPLEMENTED / LOCAL VERIFICATION PENDING**.
- Keep Batch C in one MR unless verification exposes a real blocker.

## Ownership boundary carried forward

```text
PDF / GDT acquisition / invoice persistence / normalized source detail -> Modules\Invoices
Inventory invoice inbox / matching / receipt proposal / stock workflow -> Modules\Inventory
```

Critical invariant:

```text
Invoice sync/import != stock posting
```

Inventory does not read `storage/app/invoices/pdf`, does not parse invoice PDF and does not update `Product.quantity`.

Physical stock still changes only through the Batch A receipt confirmation path and `ReceiptPostingService` / canonical stock posting services.

## Batch C implementation

### Invoices producer boundary

Added:

```text
Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php
Modules/Invoices/Integrations/Inventory/InvoiceInventoryHandoffService.php
Modules/Invoices/Integrations/Inventory/PurchaseInvoiceInventoryQueryService.php
```

The producer:

- accepts purchase invoices only;
- fetches structured GDT detail through the existing `GdtPdfService::fetchDetail()` source boundary;
- uses `hdhhdvu` structured line data already used by the Invoices PDF renderer;
- builds explicit contract version `1.0`;
- preserves seller/header/totals/line documentary evidence;
- never makes Inventory parse PDF or inspect invoice storage;
- invokes Inventory only through an explicit handoff service, not an Eloquent observer.

### Inventory integration inbox

Added tables:

```text
inventory_invoice_inbox
inventory_invoice_inbox_lines
```

Inbox identity is protected by:

```text
(source_module, source_invoice_identity, integration_purpose)
```

Each normalized payload also stores `normalized_payload_hash`.

Replay behavior:

- unchanged payload -> update `last_seen_at` only;
- changed payload -> refresh eligible inbox data;
- if linked receipt is no longer DRAFT -> changed payload is rejected and confirmed history is not rewritten.

Statuses:

```text
RECEIVED
MATCHING
REVIEW_REQUIRED
READY
RECEIPT_CREATED
ERROR
```

### Deterministic product matching

`InventoryItemMatchingService` applies this order:

```text
1. confirmed supplier alias
2. exact source product-code alias
3. exact active InventoryItem display-name match when unique
4. UNRESOLVED for human review
```

There is no fuzzy/AI auto-confirm.

Human review supports:

- select existing InventoryItem;
- remember reviewed alias with supplier/source provenance;
- mark explicit `NON_STOCK`;
- create standalone InventoryItem when `inventory.item.manage` is allowed.

Failed match remains `UNRESOLVED`; it is never silently treated as `NON_STOCK`.

### Draft receipt proposal

`InvoiceReceiptProposalService`:

- requires all lines to be resolved/classified;
- excludes explicit `NON_STOCK` lines;
- creates or refreshes only a `DRAFT` receipt;
- stores invoice seller/source/item/UOM/lot/HSD evidence on the draft;
- refuses to rewrite a non-DRAFT linked receipt;
- never calls `ReceiptPostingService` or `StockPostingService`.

The operator must still open/confirm the receipt through the Batch B receipt workflow before stock changes.

### Admin UI

New route:

```text
GET /admin/inventory/invoice-inbox
admin.inventory.invoice-inbox
permission: inventory.receipt.view
```

The dashboard now surfaces `Hóa đơn chờ nhập kho`.

The new Livewire workspace provides:

- bounded pagination `10 / 25 / 50 / 100`;
- recent purchase-invoice candidates through the Invoices integration query service;
- explicit sync into Inventory Inbox;
- deterministic/manual matching review;
- explicit NON_STOCK classification;
- standalone InventoryItem creation with permission gate;
- warehouse selection;
- create/refresh DRAFT receipt action;
- direct path to the existing receipt workspace;
- visible reminder that synchronization does not change stock.

Batch C also adds an idempotent Admin-menu migration for `Hóa đơn chờ nhập kho` so existing databases receive the new child menu entry.

## Tests added

```text
tests/Feature/Inventory/InventoryBatchCInvoiceIntegrationContractTest.php
tests/Feature/Invoices/InvoiceInventoryHandoffContractTest.php
```

Coverage includes:

- source ownership stays in Invoices;
- V1 contract boundary;
- GDT structured detail / `hdhhdvu` source;
- no Inventory PDF-storage ownership leak;
- inbox idempotency keys and payload hash;
- deterministic matching / no fuzzy auto-confirm;
- explicit human classification actions;
- draft-only receipt proposal with no posting service call;
- new Admin route, pagination and input/loading visual contract.

## Verification gate — pending local execution

Run only Inventory + directly impacted Invoices tests.

```bash
php artisan test tests/Feature/Inventory
php artisan test tests/Feature/Invoices/InvoiceInventoryHandoffContractTest.php
./vendor/bin/pint Modules/Inventory Modules/Invoices/Integrations/Inventory tests/Feature/Inventory tests/Feature/Invoices/InvoiceInventoryHandoffContractTest.php
```

No full-project regression is required.

After Pint, review any changed files before committing formatting changes.

## Migration / UI gate after tests PASS

```bash
php artisan migrate
```

Manual UI check:

```text
/admin/inventory
/admin/inventory/invoice-inbox
/admin/inventory/receipts
```

Verify desktop/tablet/mobile, especially:

- purchase-invoice candidate sync;
- GDT-detail error handling when token/session is unavailable;
- inbox replay does not duplicate source identity;
- line matching / NON_STOCK / standalone item actions;
- unresolved lines block receipt proposal;
- warehouse selection;
- DRAFT receipt creation;
- no stock movement/balance change before receipt confirmation;
- receipt confirmation still uses existing Batch A/Batch B flow.

Report `UI PASS` only after these checks.

## Deferred to Batch D

- Excel audit/export;
- selected-vs-all-filtered export semantics;
- final query/index/runtime hardening;
- final module closeout.

## Stop gate

Do not open/merge the Batch C MR until:

1. Inventory focused tests PASS;
2. directly impacted Invoices handoff test PASS;
3. Pint PASS;
4. migration PASS;
5. manual UI smoke reports `UI PASS`;
6. final branch diff/status is clean.

Do not start Batch D automatically before Batch C is accepted/merged.
