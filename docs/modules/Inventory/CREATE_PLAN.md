# Inventory Module — CREATE PLAN

Status: **AWAITING IMPLEMENTATION APPROVAL**  
Target module: `Modules\Inventory`  
Module type: `domain`  
Planning date: 2026-09-09  
Business specification: `docs/modules/Inventory/REQUIREMENTS.md`

> This document is the `/create-module Inventory` planning output. It authorizes no application-code implementation until the user explicitly approves this plan.

---

# 1. Purpose and Implementation Goal

Create `Modules\Inventory` as the canonical ERP owner for physical stock operations while preserving existing ownership boundaries:

- `Invoices` owns invoice acquisition, persistence, normalization, PDF/file metadata and `storage/app/invoices/pdf`;
- `Inventory` owns warehouses, operational stock documents, lots/HSD, stock movement ledger, stock balance, inventory-side invoice inbox/matching and inventory audit/export;
- `Product` remains the general product/catalog master;
- `Pharma` remains the pharmaceutical medicine/intelligence master;
- `Partner` remains the supplier/customer/organization master;
- `Shared` remains the canonical reusable import/export/UI infrastructure.

Central invariant:

```text
Invoice sync/import != stock posting
```

A purchase invoice may create/update a **draft receipt proposal** only. Physical stock changes only after explicit, authorized receipt confirmation.

---

# 2. Reference Modules and Conventions Reused

## 2.1 Invoices

Relevant because it owns the source domain feeding normalized invoice data to Inventory.

Reuse:

- domain-module manifest pattern;
- Admin route/permission discipline;
- explicit service/application boundaries;
- idempotent source identity concepts;
- protected invoice/file ownership.

Do NOT copy:

- GDT/MeInvoice acquisition;
- invoice PDF parsing/generation/storage;
- invoice identity ownership;
- GDT credentials/tokens;
- invoice backup/recovery responsibilities.

Inventory will consume only a versioned normalized integration contract.

## 2.2 Pharma

Relevant because Inventory must support pharmaceutical stock with deterministic matching, lot/HSD and human-reviewed source mappings.

Reuse:

- deterministic matching before fuzzy/AI logic;
- source/provenance-aware human review;
- capability-specific Admin permissions;
- bounded Admin pagination and operational workspace patterns;
- explicit cross-module ownership boundaries.

Do NOT copy:

- medicine master ownership;
- Pharma source intelligence/acquisition;
- procurement ownership;
- Pharma facility/source synchronization.

## 2.3 Partner

Relevant because invoice suppliers may be unresolved and because external/source-derived identity must not directly mutate canonical Partner data.

Reuse:

- candidate/review philosophy;
- provenance snapshots;
- explicit human-controlled canonical mutation;
- idempotent source mapping.

Do NOT copy:

- Partner master mutations into Inventory;
- business lookup/crawler logic;
- Partner-specific source-reference ownership.

## 2.4 Shared

`Modules\Shared` is a required infrastructure dependency for the Inventory Excel export/import surfaces approved for v1 and later opening-balance/stocktake templates.

Inventory will reuse the existing Shared Import/Export foundation instead of creating a module-private export framework.

---

# 3. Bootstrap Contract

| Capability | Plan |
|---|---|
| Manifest | `Modules/Inventory/config/module.php` |
| Type | `domain` |
| Default state | `enabled => false` initially for safe staged rollout |
| Dependencies | `['Shared']` only |
| Optional integrations | Invoices, Product, Pharma, Partner — **not** hard manifest dependencies |
| Module Provider | Not required initially |
| Config | Yes |
| Web routes | Yes |
| API routes | No in v1 |
| Migrations | Yes |
| Livewire | Yes |
| Blade components | Prefer existing Shared/Admin components; module-local only if justified |
| Console commands | No in v1 |
| Runtime state | Supported through existing module state infrastructure |
| Runtime storage | Private export/temp storage only; no PDF ownership |
| `module.json` | Forbidden / not used |

## Why only `Shared` is a hard dependency

Inventory must continue to support manual receipts, issues, transfers, stocktakes and stock reporting even when Invoices, Pharma, Product or Partner are runtime-disabled.

`Shared` is already a required shell module and supplies canonical reusable import/export infrastructure, so declaring it as the sole hard dependency is safe and meaningful.

---

# 4. Proposed Module Structure

```text
Modules/Inventory/
├── config/
│   └── module.php
├── routes/
│   └── web.php
├── Http/Controllers/
│   └── InventoryDashboardController.php
├── Livewire/
│   ├── Dashboard/
│   ├── Warehouses/
│   ├── Items/
│   ├── Receipts/
│   ├── Issues/
│   ├── Transfers/
│   ├── Stocktakes/
│   ├── Stock/
│   ├── Lots/
│   ├── Movements/
│   └── InvoiceInbox/
├── Models/
├── Services/
├── Actions/
├── DTOs/
├── Enums/
├── Integrations/
│   ├── Invoices/
│   ├── Product/
│   ├── Pharma/
│   └── Partner/
├── Exports/
├── Imports/
├── database/migrations/
└── resources/views/
    ├── dashboard.blade.php
    └── livewire/...
```

No custom provider is planned unless implementation proves a registration need not supported by `Modules\ModuleServiceProvider`.

---

# 5. Database / Model Plan

## 5.1 `inventory_warehouses`

Purpose: canonical warehouse master inside Inventory.

Key columns:

```text
id
code unique
name
partner_id nullable integration reference
address nullable
province_code nullable
is_active
created_by nullable
updated_by nullable
timestamps
```

No negative-stock override column in v1.

## 5.2 `inventory_items`

Purpose: stock identity without duplicating Product/Pharma masters.

Key columns:

```text
id
sku unique
display_name
base_uom
product_id nullable
pharma_medicine_id nullable
lot_tracking boolean
expiry_tracking boolean
allow_fractional_quantity boolean
reorder_level decimal nullable
is_active
metadata json nullable
timestamps
```

Standalone item is permitted. Product/Pharma references are nullable.

Cross-module FK strategy will be conservative: avoid database-level foreign keys to optional runtime modules unless repository compatibility proves they are safe across module lifecycle. IDs may be indexed integration references instead.

## 5.3 `inventory_item_aliases`

Purpose: persistent reviewed deterministic invoice-line mapping.

Candidate columns:

```text
id
inventory_item_id
supplier_partner_id nullable
supplier_tax_code nullable
source_product_code nullable
normalized_description_key
uom_key nullable
package_key nullable
source = invoices
confirmed_by
confirmed_at
metadata json nullable
timestamps
```

Unique/index strategy must prevent duplicate accepted alias identity while allowing different suppliers to use the same textual description.

## 5.4 `inventory_lots`

Key columns:

```text
id
inventory_item_id
lot_number
expiry_date nullable
manufacture_date nullable
supplier_partner_id nullable
source_receipt_line_id nullable
status
metadata json nullable
timestamps
```

Do not globally unique `lot_number`.

Planned uniqueness is item-scoped and must account for expiry disambiguation. Final migration will use a deterministic normalized lot identity/key rather than nullable-column uniqueness that behaves inconsistently across MySQL null semantics.

## 5.5 Operational documents

Tables:

```text
inventory_receipts
inventory_receipt_lines
inventory_issues
inventory_issue_lines
inventory_transfers
inventory_transfer_lines
inventory_stocktakes
inventory_stocktake_lines
```

Shared document rules:

- statuses use explicit enums/validated strings;
- draft documents editable;
- confirmed documents immutable for stock history;
- cancellation only before posting unless a separately modeled reversal exists;
- actor/timestamp fields preserved;
- quantities use fixed-precision decimal, not float;
- monetary evidence uses decimal, not float;
- snapshot fields preserve historical seller/item/source evidence.

## 5.6 `inventory_movements`

Canonical append-only stock ledger.

Candidate columns:

```text
id
movement_key unique
movement_type
warehouse_id
inventory_item_id
lot_id nullable
quantity_delta decimal
base_uom
document_type
document_id
document_line_id nullable
movement_role
source_type
source_identity_key nullable
occurred_at
posted_by
reversal_of_movement_id nullable
metadata json nullable
created_at
```

No update/delete workflow for confirmed movements.

`movement_key` is deterministic/idempotent and prevents duplicate posting from retries/double-submit.

## 5.7 `inventory_balances`

Projection for efficient reads.

Candidate key:

```text
warehouse_id + inventory_item_id + lot_key
```

Because `lot_id` may be nullable, migration design must avoid relying on a nullable composite unique index that allows duplicate NULL rows. Plan: persist an explicit normalized balance dimension key (e.g. `lot_key = 'NO_LOT'` or deterministic numeric/hash representation) or separate non-lot/lot constraints after MySQL validation.

Fields:

```text
quantity_on_hand decimal
updated_at
```

Balance is never directly imported or independently edited.

## 5.8 Invoice integration inbox

Tables:

```text
inventory_invoice_inbox
inventory_invoice_inbox_lines
```

Header identity:

```text
source_module
source_invoice_identity
integration_purpose = purchase_receipt
contract_version
source_invoice_id nullable
normalized_payload_hash
processing_status
receipt_id nullable
seller/buyer/totals snapshots
received_at
last_seen_at
```

Database unique contract:

```text
(source_module, source_invoice_identity, integration_purpose)
```

Lines store normalized documentary evidence and matching/classification state.

Replay with unchanged hash is no-op/update-last-seen. Replay with changed normalized hash refreshes only eligible draft proposal data and never rewrites confirmed stock history.

## 5.9 Audit

Prefer ledger + immutable document actor/timestamps + framework/domain audit event records sufficient for critical actions.

If repository inspection during implementation shows no stable shared audit infrastructure, create a narrow `inventory_audit_logs` table for high-risk Inventory actions only, not a generic system-wide audit framework.

---

# 6. Service Boundaries

Planned core services/actions:

```text
InventoryDashboardService
WarehouseService
InventoryItemService
InventoryItemMatchingService
ReceiptService
ReceiptPostingService
IssueService
IssuePostingService
TransferService
TransferPostingService
StocktakeService
StocktakePostingService
LotService
StockMovementService
StockBalanceService
InventoryQueryService
InventoryExportService
```

Rules:

- controllers/page Blade contain no domain queries;
- Livewire owns UI state/validation/orchestration only;
- posting services own transactions, locks, idempotency and invariants;
- movement/balance mutation is centralized; no arbitrary model writes from UI;
- all confirm operations authorize caller at the boundary and re-check domain invariants in service/actions.

---

# 7. Transaction, Concurrency and Idempotency Plan

## Receipt confirmation

Within one DB transaction:

1. lock receipt row (`SELECT ... FOR UPDATE` / Laravel lock equivalent);
2. verify status is DRAFT;
3. validate lines/item/UOM/lot/HSD;
4. resolve/create lot safely;
5. generate deterministic movement keys;
6. insert movements under unique constraints;
7. lock/update balance rows;
8. record actor/time/audit;
9. transition receipt to CONFIRMED.

Any failure rolls back the full posting.

## Issue confirmation

Within one transaction:

- lock document;
- lock relevant balances in deterministic order;
- verify sufficient on-hand quantity;
- deny negative stock;
- insert movement(s);
- update balance projection;
- confirm document.

## Transfer confirmation

One atomic transaction creates both source OUT and destination IN movements and balance updates.

## Stocktake confirmation

Lock target balance dimensions, calculate approved variance, create only variance movements, update balances atomically, then confirm.

## Duplicate protection

Use all three layers:

```text
integration unique key
+ document state/row lock
+ deterministic movement unique key
```

UI loading/disabled state prevents accidental repeat submission but is not the data-integrity mechanism.

---

# 8. Invoices -> Inventory Integration Plan

## Ownership

The normalized contract is exposed at an **Invoices integration boundary**; Inventory consumes it through `Modules\Inventory\Integrations\Invoices`.

Inventory must not query invoice PDFs or `storage/app/invoices/pdf`.

## Contract shape

Implementation should introduce a versioned DTO/value object equivalent to:

```text
InvoiceForInventoryV1
InvoiceLineForInventoryV1
```

containing the approved REQUIREMENTS fields.

The source adapter belongs to Invoices; the consumer/validator/inbox service belongs to Inventory.

## Invocation

Initial v1:

```text
Invoices explicit action/service
-> build normalized V1 contract
-> Inventory integration gateway
-> upsert inbox
-> create/update draft receipt proposal
```

Do not use hidden Eloquent observers.

Queue is optional in v1; inbox/idempotency is mandatory.

## Contract failure

- unsupported major version -> reject safely and log structured integration error;
- malformed required identity -> reject, no receipt creation;
- Inventory disabled -> Invoices sync remains valid; integration handoff must not make invoice persistence fail merely because Inventory is unavailable;
- retry later must remain idempotent.

This non-blocking behavior is why Invoices is not a hard module dependency.

## Current invoice data gap

Current Invoices canonical model persists header/totals but not a complete normalized line collection. Therefore implementation must first prove where normalized GDT line data currently exists during processing and expose it through an explicit V1 contract.

If no durable normalized line persistence exists, v1 may build the contract at the existing Invoices normalization/service boundary and submit it to Inventory inbox. Inventory must never solve this gap by parsing the PDF fixture.

The current sample PDF remains a **reference fixture** for expected normalized contract values only.

---

# 9. Product / Pharma / Partner Integration Plan

## Product

- InventoryItem may optionally reference Product ID.
- Product remains catalog owner.
- Do not update `Product.quantity` as a second canonical stock source.
- Before any compatibility projection is added, run a caller/data audit of `wp_products.quantity`.
- v1 Inventory posting must work without changing Product.quantity unless a separately proven compatibility adapter is approved.

## Pharma

- optional Pharma medicine mapping/reference;
- may consume stable medicine identity/name/package/manufacturer fields for candidate ranking;
- no Pharma master mutation from Inventory/invoice text.

## Partner

- receipt stores partner reference when resolved;
- unresolved supplier is allowed;
- immutable seller name/tax/address snapshots retained;
- no automatic Partner creation/update;
- optional deep link/candidate workflow may be added later, not required for core posting.

---

# 10. Product Matching Design

Matching order:

```text
1. confirmed supplier/item alias
2. exact source product code alias
3. exact deterministic normalized key candidate
4. candidate list for human review
```

No fuzzy/AI auto-confirm in v1.

Line classification:

```text
STOCK
NON_STOCK
UNRESOLVED
```

Rules:

- failed match != NON_STOCK;
- operator explicitly marks NON_STOCK;
- required stock line cannot remain UNRESOLVED at receipt confirmation;
- confirmed mapping may create/update reviewed alias;
- alias creation is idempotent and provenance-aware.

Matching UI should show:

- raw invoice description;
- normalized description;
- UOM/quantity;
- lot/HSD/manufacturer evidence;
- suggested item and reason;
- Product/Pharma references when available;
- explicit Create standalone InventoryItem / Select item / Mark non-stock actions according to permissions.

---

# 11. UOM and Quantity Plan

Use fixed precision decimal quantity (proposed `decimal(20,6)` unless implementation data audit justifies another precision).

Each InventoryItem has one base stock UOM.

Persist movement quantity in base UOM.

Receipt/invoice line preserves original documentary:

```text
source_quantity
source_uom
conversion_factor
base_quantity
base_uom
```

Conversion is item/package scoped.

No unsafe global rule such as `1 box = N units` based only on text UOM names.

---

# 12. Lot / HSD Plan

Lot/expiry are first-class.

Receipt confirm rules:

- lot-tracked item -> lot required;
- expiry-tracked item -> expiry required;
- invalid/past expiry handling is explicit validation/warning policy; stock history must preserve entered approved evidence;
- same textual lot on different items is allowed.

Issue UX SHOULD support FEFO suggestion after core posting is stable, but v1 confirmation remains explicit operator review.

---

# 13. Admin Routes and Workspaces

Canonical route family:

```text
/admin/inventory
/admin/inventory/warehouses
/admin/inventory/items
/admin/inventory/receipts
/admin/inventory/issues
/admin/inventory/transfers
/admin/inventory/stocktakes
/admin/inventory/stock
/admin/inventory/lots
/admin/inventory/movements
/admin/inventory/invoices
```

Route names use `admin.inventory.*` unless repository route inspection at implementation time proves another canonical naming convention is required.

All routes use existing Admin auth and capability-specific authorization.

## Dashboard

Primary KPI/action surfaces:

- total active warehouses;
- total inventory items;
- draft receipts awaiting review;
- invoice lines unresolved/matching required;
- low-stock items;
- expiring lots;
- stocktake discrepancies/pending confirmation;
- recent confirmed movements;
- integrity/anomaly warnings.

Dashboard links deep-link into filtered workspaces.

## Workspace-first UI

Follow `.codex/standards/ADMIN_UI_STANDARD.md`:

- canonical `Admin::layouts.master` shell where applicable;
- page Blade is shell only;
- class-based Livewire feature UI;
- visible bordered inputs;
- shared searchable select where applicable;
- responsive tables;
- explicit loading/empty/error states;
- centered confirmation modals for destructive/high-risk actions;
- disabled/loading state during confirm/post operations;
- return/deep-link path to Dashboard from child workspaces.

---

# 14. Search / Filter / Pagination / Selection Plan

All production list workspaces use bounded pagination:

```text
10 / 25 / 50 / 100
```

No `All` page size.

Examples:

## Stock balance

Search: SKU / item name  
Filters: warehouse, stock state, lot/expiry tracking, low stock  
Sort: item/SKU/on-hand  
Export: all matching or selected

## Lots

Search: SKU/item/lot  
Filters: warehouse, expiry range, expired/expiring/valid  
Export supported

## Movements

Search: document/item/SKU  
Filters: warehouse, movement type, date range, source/document type  
Read-only; export supported

## Receipts/issues/transfers/stocktakes

Search: document number/item/supplier snapshot  
Filters: status, warehouse, date range/source  
Selection only where bulk export or safe non-destructive action is meaningful.

Filter change resets pagination and selection.

---

# 15. Checkbox / Bulk Action Evaluation

Checkboxes are **not** required on every operational document list merely to satisfy a UI checklist.

Use selection where it has a safe, clear purpose:

- stock balances/lots/movements: selected-row export;
- receipt/invoice inbox lines: selected matching/review operations only when semantics are explicit;
- no bulk confirm of receipts/issues/transfers/stocktakes in v1 because posting is high-risk and document-specific;
- no bulk delete of confirmed stock history.

Selected count must be shown.

---

# 16. Excel Import / Export Plan

## Export — MUST HAVE

Use Shared Import/Export infrastructure.

Export scopes:

- stock balances;
- movements;
- lots/HSD;
- receipts;
- issues;
- transfers;
- stocktakes;
- invoice matching/audit where useful.

Canonical semantics:

```text
selected IDs present -> export selected
no selected IDs      -> export all records matching approved active filters
```

Never current-page-only by accident.

Large exports use chunk/lazy/queued mechanisms when volume requires it.

Generated files are private and use controlled download/handoff.

## Import — limited v1 scope

Do NOT import directly into:

```text
inventory_movements
inventory_balances
```

Potential approved import surfaces:

- Warehouse master;
- InventoryItem master/mapping;
- stocktake count template/import;
- controlled opening balance migration workflow.

Opening balance import must create a controlled posting document/adjustment and movement ledger records; it must never directly set balance rows.

If opening balance is not required for the first deployment dataset, defer its UI while keeping schema/service extension points clear.

---

# 17. Permissions

Manifest permission candidates:

```text
inventory.view
inventory.warehouse.manage
inventory.item.manage
inventory.receipt.view
inventory.receipt.create
inventory.receipt.confirm
inventory.issue.view
inventory.issue.create
inventory.issue.confirm
inventory.transfer.view
inventory.transfer.create
inventory.transfer.confirm
inventory.stocktake.view
inventory.stocktake.create
inventory.stocktake.confirm
inventory.match.review
inventory.audit.view
inventory.export
inventory.import
```

Implementation must align permission seeding/registration with the repository's existing Admin/Role mechanism; do not create a second authorization registry.

Confirm permissions are deliberately separate from create/edit.

---

# 18. Runtime State and Module Disable Behavior

Manifest default: disabled for staged rollout.

Runtime enable/disable uses existing `ModuleStateRepository` / `ModuleStateResolver`; Inventory does not access `storage/app/system/module-state.json` directly.

Expected behavior:

- Inventory OFF: its routes/UI/resources do not boot;
- Invoices remains operational and its persistence/sync cannot fail just because Inventory is OFF;
- integration handoff should detect availability and fail/defer safely;
- Inventory ON while Product/Partner/Pharma/Invoices OFF must still permit manual core stock operations where references are optional;
- runtime toggle must not mutate tracked manifest files;
- Git remains clean after toggles.

---

# 19. Runtime Storage / Docker

Inventory does not own invoice PDF storage.

V1 runtime storage is limited to private generated files such as:

```text
storage/app/inventory/exports
storage/app/inventory/imports
storage/app/inventory/tmp
```

Exact path should follow Shared Import/Export storage conventions where possible rather than inventing redundant directories.

Implementation must inspect `Dockerfile` and `docker/entrypoint.sh` before creating runtime directories. Ensure `www-data` write access without `chmod 777`, and account for root CLI versus PHP-FPM ownership.

No new persistent binary storage is required for core stock records.

---

# 20. Security / Integrity Plan

Mandatory:

- Admin auth + capability-specific backend authorization;
- strict validation for IDs/statuses/quantities/UOM/date filters;
- fixed-precision decimals for quantity/money;
- no browser-supplied model class/table/path execution;
- no direct balance mutation endpoint;
- confirmed movement immutability;
- private export/import storage;
- no raw exceptions returned to browser;
- structured/redacted integration errors;
- transaction + row locks for posting;
- DB unique constraints for idempotency;
- server-controlled file download identifiers;
- mass-assignment allowlists;
- XSS-safe rendering of invoice descriptions and snapshots.

---

# 21. Dashboard / Query Performance Plan

`InventoryDashboardService` should aggregate bounded operational metrics with indexed queries rather than loading collections.

Indexes will target real filters:

- warehouse/status/date on operational documents;
- inventory item/SKU/name where searchable;
- expiry date on lots;
- warehouse/item/lot dimension on balances;
- document/source/date/item on movements;
- source invoice identity/hash/status on integration inbox;
- match status/classification on inbox lines.

Avoid per-row balance/lot queries in lists; use joins/eager loading/aggregates.

---

# 22. Tests and Acceptance Gates

## Focused tests

1. Module manifest/discovery/bootstrap.
2. Runtime ON/OFF and Git-clean contract.
3. Permission route/action denial/allowance.
4. Warehouse/item validations.
5. Receipt DRAFT -> CONFIRMED posting.
6. Receipt double-confirm protection.
7. Issue insufficient-stock rejection.
8. Issue/transfer concurrency protection.
9. Atomic transfer OUT+IN.
10. Stocktake variance posting.
11. Movement immutability/idempotent keys.
12. Balance projection consistency.
13. Lot/HSD required validation.
14. Invoice V1 contract validation.
15. Same invoice replay does not duplicate inbox/receipt/movements.
16. Changed invoice payload refreshes draft only and never rewrites confirmed stock.
17. Product matching/alias deterministic behavior.
18. Unresolved Partner allowed with snapshots preserved.
19. Search/filter/page-size normalization.
20. Selected-vs-all-filtered export semantics.
21. No direct import into balances/movements.

## Reference fixture contract

Use the approved current invoice fixture's normalized expected values, including:

- Biviantac Fort / lot `041224` / HSD `2027-12-21`;
- Atirin Suspension / lot `5328` / HSD `2027-05-08`;
- seller tax code `0317953611`.

Inventory tests consume normalized DTO fixture data; they do not parse the PDF.

## Regression

After implementation batches:

- Inventory focused/module tests;
- Invoices regression when integration adapter changes;
- Shared import/export regression when reused/extended;
- Product regression only when compatibility adapter/caller changes are introduced;
- Pharma/Partner regression only when their explicit integration boundaries are changed;
- System module-state tests when manifest/runtime behavior is involved;
- Admin regression for menu/permission/UI shell integration.

Full-project regression is not required after every small batch; run at final integration/release gate when appropriate under repository workflow.

## UI acceptance

Representative desktop/tablet/mobile widths:

- Dashboard hierarchy/actions;
- input borders/focus/error states;
- responsive filters/tables;
- pagination white/inactive + indigo/active contract;
- matching workspace usability;
- confirm modals/loading state;
- no accidental double-submit;
- selected export semantics;
- empty/loading/error states;
- no 404/500/important console error.

---

# 23. Implementation Batch / MR Plan

To reduce repeated pull/test cycles, group work into **four coherent batches** rather than many tiny MRs.

## Batch A — Foundation + Persistence + Core Ledger

Create:

- module skeleton/manifest/routes shell;
- migrations/models/enums/DTO foundations;
- Warehouse + InventoryItem + Lot;
- Movement + Balance;
- receipt/issue/transfer/stocktake document persistence;
- posting services with transactions/locks/idempotency;
- focused service/schema tests;
- initial `MODULE.md` ownership contract.

No Invoices integration yet.

## Batch B — Admin Operational Workspaces

Create:

- Inventory Dashboard;
- warehouse/item management;
- receipt/issue/transfer/stocktake workspaces;
- stock balance/lots/movements browsers;
- permissions/menu integration;
- search/filter/pagination;
- confirmation modals/loading states;
- Admin/UI tests.

## Batch C — Invoices Integration + Product Matching

Implement:

- Invoices V1 normalized contract producer/adapter;
- Inventory integration gateway/inbox;
- purchase invoice -> draft receipt proposal;
- line classification/matching;
- InventoryItem aliases;
- unresolved Partner snapshots;
- Product/Pharma optional candidate references;
- fixture-based contract/idempotency tests.

Do not parse PDFs in Inventory.

## Batch D — Excel Audit/Export + Hardening + Closeout

Implement:

- Shared-based exports;
- selected/all-filtered semantics;
- stocktake/import template only if required for first operational rollout;
- query/index hardening;
- runtime-state verification;
- focused impacted regressions;
- build/Pint;
- manual UI smoke;
- documentation/handoff closeout.

This grouping preserves reviewability while minimizing repeated test/pull cycles.

---

# 24. Files Expected to Be Created / Changed

## New Inventory-owned files

- `Modules/Inventory/**`
- `docs/modules/Inventory/MODULE.md`
- `docs/modules/Inventory/README.md`
- `docs/modules/Inventory/INFORMATION.md`
- `docs/modules/Inventory/COLLABORATION_HANDOFF.md`
- tests under the repository's established Inventory feature/unit paths.

## Expected cross-module changes

Only when the relevant batch is approved:

- `Modules/Invoices/**` — explicit normalized Inventory contract producer/adapter and tests;
- Admin menu/permission seed/config files — Inventory navigation/capabilities;
- possibly Shared import/export extension only if existing contract lacks a required generic capability.

## Explicitly NOT planned in initial implementation

- moving/parsing invoice PDFs;
- changing `storage/app/invoices/pdf` ownership;
- destructive change/removal of `Product.quantity`;
- automatic Partner creation/update;
- automatic Pharma master mutation;
- sold invoice auto-issue;
- accounting valuation engine;
- negative stock override;
- bin/shelf/serial tracking;
- two-step transfer transit;
- fuzzy/AI auto-matching.

---

# 25. Risks and Mitigations

## Risk: dual stock sources

`Product.quantity` exists today.

Mitigation: Inventory becomes canonical physical stock; do not dual-write Product.quantity in core posting. Audit callers before any compatibility projection.

## Risk: invoice line data not durably persisted

Current Invoices model is header-oriented.

Mitigation: expose normalized line contract at existing Invoices normalization/service boundary; persist the complete normalized contract in Inventory inbox before draft generation.

## Risk: double posting under concurrency

Mitigation: DB transaction + row lock + document state + deterministic movement unique key.

## Risk: nullable composite uniqueness for balances/lots

Mitigation: explicit normalized dimension/identity key rather than trusting nullable composite unique behavior.

## Risk: optional module runtime dependencies

Mitigation: only `Shared` is hard dependency; all business integrations fail/degrade safely when source modules are disabled.

## Risk: import bypassing ledger

Mitigation: never import directly into movements/balances; all opening/stocktake imports post through controlled documents.

## Risk: warehouse list/report performance

Mitigation: indexed balance projection, bounded pagination, eager/aggregate queries, chunked exports.

---

# 26. Implementation Preconditions

Before Batch A application code begins:

1. user explicitly approves this `CREATE_PLAN.md`;
2. implementation branch strategy is confirmed under `docs/GITHUB_COLLABORATION_WORKFLOW.md`;
3. re-read current `main`/target branch module provider + Admin UI standard before writes;
4. verify `Modules/Inventory` still does not exist;
5. inspect current permission/menu seeding convention;
6. inspect Docker/runtime storage only before any runtime-file feature;
7. keep the reference PDF on the Invoices side; Inventory receives normalized fixture/DTO only.

---

# 27. Plan Readiness

```text
Business requirements        : READY
Module ownership/boundary    : READY
Bootstrap Contract           : READY
Hard dependencies            : READY (`Shared` only)
Optional integrations        : READY
Database model               : READY FOR IMPLEMENTATION
State machines               : READY
Permissions                  : READY
Admin UI                     : READY
Idempotency/concurrency      : READY
Invoices integration concept : READY
Import/export                : READY
Runtime state                : READY
Runtime storage              : READY
Testing strategy             : READY
```

## Overall

**READY FOR IMPLEMENTATION AFTER EXPLICIT USER APPROVAL OF THIS CREATE PLAN.**

No application code, migration, route, model, service, Livewire component, permission, menu entry or runtime state is authorized by this document alone.
