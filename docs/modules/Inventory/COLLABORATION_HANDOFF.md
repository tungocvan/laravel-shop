# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B — Admin Dashboard + UI/UX: **MERGED / VERIFIED / UI PASS** via PR #179, merge commit `df406fae`.
- Batch C branch: `feat/inventory-batch-c-invoice-integration`.
- Batch C — Invoices Integration + Product Matching + Draft Receipt: **IMPLEMENTED / FOCUSED TESTS PASS**.
- Batch C2 — Bulk Intake & Normalization: **IMPLEMENTED / GATE 2–4 PASS**.
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

## Batch C producer / integration boundary

Invoices provides:

```text
Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php
Modules/Invoices/Integrations/Inventory/InvoiceInventoryHandoffService.php
Modules/Invoices/Integrations/Inventory/PurchaseInvoiceInventoryQueryService.php
```

The producer:

- accepts purchase invoices only;
- uses structured GDT detail / `hdhhdvu`, not PDF parsing;
- builds explicit contract version `1.0`;
- preserves seller/header/totals/line documentary evidence;
- invokes Inventory through an explicit service boundary rather than an Eloquent observer.

## Batch C2 durable intake staging

Invoices owns these staging tables:

```text
invoice_inventory_snapshots
invoice_inventory_staging_lines
```

Snapshot behavior:

- creates a durable `PENDING` row before remote fetch;
- records `FETCHING`, attempts and `last_attempt_at`;
- stores raw GDT detail payload plus SHA-256 payload hash;
- stores `ERROR` and `last_error` for first-attempt and retry failures;
- unchanged payloads are idempotent;
- completed snapshots become `NORMALIZED`.

Staging lines preserve both raw and normalized evidence, including:

```text
raw_description
source_product_code
source_quantity
source_uom
unit_price
line_amount
tax_rate
normalized_name
strength
dosage_form
package_spec
manufacturer
normalized_uom
lot_number
manufacture_date
expiry_date
raw_payload
normalization_meta
```

`tax_rate` is stored as source documentary text rather than forced numeric data so values such as non-standard tax markers are not destroyed.

Lot / manufacture date / expiry date remain transaction/lot-instance evidence and are not copied into item master data.

## Deterministic normalization

`InvoiceLineNormalizer` separates item identity from transactional details. Current deterministic parser covers name, strength, dosage form, package specification, UOM, lot and expiry/manufacture dates.

Rules remain deterministic only. No fuzzy/AI match is allowed to auto-confirm an InventoryItem.

## Bulk intake UI

Canonical route:

```text
GET /admin/inventory/intake
admin.inventory.intake
```

Workspace: `ReceivingIntakeWorkspace`.

The workflow is:

```text
Invoices local purchase headers
  -> bounded date-range queue dispatch
  -> GDT structured detail
  -> RAW snapshot
  -> staging lines
  -> deterministic normalization
  -> select normalized snapshots
  -> publish Inventory Inbox
  -> exception review / matching
  -> READY
  -> create Receipt DRAFT
  -> operator confirms receipt separately
  -> stock posting
```

The bulk dispatcher:

- works only with local `purchase` invoice headers;
- is bounded by date range;
- clamps batch size to max 500;
- uses `chunkById`;
- dispatches `StageInvoiceForInventory` to queue `default`;
- is resumable/idempotent through staging snapshot identity and payload hash.

Important: the current C2 bulk dispatcher stages detail for purchase invoice headers already present in the local `invoices` table. Full remote acquisition of all 2026 invoice headers is not claimed as part of this implementation.

## Publication from staged data

`InvoiceForInventoryV1Factory` now prefers a `NORMALIZED` persisted snapshot when available. Bulk publication uses `buildFromSnapshot()` so already-staged data is not re-fetched from GDT.

Published line metadata retains staging provenance and source evidence including raw description, tax rate, strength, dosage form, package specification, manufacturer and raw GDT line.

`BulkInvoicePublicationService`:

- accepts selected `NORMALIZED` snapshots only;
- reuses `InventoryInvoiceIntegrationService`;
- reuses canonical deterministic matching;
- can create many draft receipts from selected invoices;
- keeps the audit rule `1 invoice = 1 Receipt DRAFT`;
- never calls `ReceiptPostingService` and never confirms stock.

## Inventory Inbox / matching / exception review

Inbox identity remains protected by:

```text
(source_module, source_invoice_identity, integration_purpose)
```

Payload replay still uses `normalized_payload_hash`.

Statuses:

```text
RECEIVED
MATCHING
REVIEW_REQUIRED
READY
RECEIPT_CREATED
ERROR
```

`InventoryItemMatchingService` order:

```text
1. confirmed supplier alias
2. exact source product-code alias
3. exact active InventoryItem display-name match when unique
4. UNRESOLVED for human review
```

Human review supports:

- single-line assign to existing InventoryItem;
- remembered supplier/source alias;
- explicit `NON_STOCK`;
- standalone InventoryItem creation with permission gate;
- select many unresolved lines in the current invoice;
- bulk assign selected lines to one InventoryItem;
- bulk mark selected lines `NON_STOCK`;
- `READY` is recalculated automatically once no `UNRESOLVED` lines remain.

Bulk actions are scoped to the selected Inbox and reject selected line IDs outside that invoice.

## Draft receipt proposal

`InvoiceReceiptProposalService`:

- requires all lines to be resolved/classified;
- excludes explicit `NON_STOCK` lines;
- creates or refreshes only a `DRAFT` receipt;
- stores source item/UOM/lot/HSD evidence;
- refuses to rewrite a non-DRAFT receipt;
- never calls stock posting services.

The operator must confirm the receipt through the existing Batch B receipt workflow before physical stock changes.

## Focused verification completed

Verified PASS during C2 gates:

```text
tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php
tests/Feature/Invoices/InvoiceInventoryHandoffContractTest.php
tests/Feature/Inventory/InventoryBatchC2BulkPublicationContractTest.php
tests/Feature/Inventory/InventoryBatchC2ExceptionReviewContractTest.php
tests/Feature/Inventory
```

Pint formatting changes were committed and branch was clean/synchronized after Gate 4.

No full-project regression is required for this batch.

## Remaining pre-MR acceptance gate

Do not run full-year 2026 intake yet.

First perform migration and a deliberately small smoke range.

### 1. Pull latest and format/test final tax-rate preservation change

```bash
git pull --ff-only origin feat/inventory-batch-c-invoice-integration

./vendor/bin/pint \
  Modules/Invoices/database/migrations/2026_09_09_170000_create_invoice_inventory_staging_tables.php \
  Modules/Invoices/Models/InvoiceInventoryStagingLine.php \
  Modules/Invoices/Integrations/Inventory/InvoiceInventoryStagingService.php \
  Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php \
  tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php

php artisan test tests/Feature/Invoices/InvoiceInventoryBulkIntakeContractTest.php
php artisan test tests/Feature/Invoices/InvoiceInventoryHandoffContractTest.php
php artisan test tests/Feature/Inventory
```

### 2. Migration

Only after those focused tests PASS:

```bash
php artisan migrate
```

Expected new tables:

```text
invoice_inventory_snapshots
invoice_inventory_staging_lines
```

### 3. Small 2026 smoke range

Use `/admin/inventory/intake` and choose a narrow known period containing only a small number of local purchase invoices, for example one day or several days rather than the whole year.

Recommended first smoke sequence:

```text
A. choose narrow From / To range
B. batch size 10–50
C. Đồng bộ & chuẩn hóa
D. allow queue worker to process jobs
E. verify snapshots / lines / ERROR count
F. inspect normalized name / quy cách / lot / HSD / tax evidence
G. select 1–3 NORMALIZED invoices
H. Publish sang Inbox
I. resolve any UNRESOLVED lines
J. verify Inbox becomes READY
K. choose test warehouse
L. create Receipt DRAFT
M. verify stock movement / stock balance did NOT change
N. do not confirm receipt unless explicitly testing the canonical posting flow
```

If GDT authentication/session is unavailable, ERROR snapshots should remain durable and retryable rather than disappearing.

### 4. UI smoke

Check desktop/tablet/mobile:

```text
/admin/inventory/intake
/admin/inventory/invoice-inbox
/admin/inventory/receipts
```

Report `UI PASS` only after:

- narrow range dispatch works;
- normalized fields are readable;
- bulk snapshot selection works;
- Inbox publication is idempotent;
- exception bulk mapping remains scoped to one invoice;
- unresolved lines block draft receipt creation;
- one selected invoice maps to one DRAFT receipt;
- no stock movement/balance changes before receipt confirmation.

## Deferred / next batches

- Full 2026 remote invoice-header acquisition strategy if local `invoices` is not already complete.
- Excel audit/export and selected-vs-all-filtered export semantics (Batch D).
- final query/index/runtime hardening.
- final module closeout.

## Stop gate

Do not open/merge Batch C/C2 until:

1. final tax-rate focused tests PASS;
2. Pint PASS;
3. migration PASS;
4. small date-range staging smoke PASS;
5. Inbox / exception-review / DRAFT receipt UI smoke reports `UI PASS`;
6. no stock mutation occurs before receipt confirmation;
7. final branch status is clean.

Do not start full-year 2026 ingestion automatically. Scale up only after the small pilot is accepted.
