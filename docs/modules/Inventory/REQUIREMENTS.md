# Inventory Module — REQUIREMENTS

Status: **APPROVED BUSINESS REQUIREMENTS**  
Target module: `Modules\Inventory`  
Module type: `domain`  
Approved date: 2026-09-09  
Source: `docs/modules/Inventory/IDEA.md`

> This document is the approved business specification for `/create-module Inventory`.
> It authorizes planning only at the next step. It does not itself create application code, migrations, routes, services, models, Livewire components, providers, seeders, jobs, commands, menu entries, permissions, or runtime state.

---

# 1. Purpose

`Inventory` is the canonical ERP owner for physical stock operations and stock state.

The module owns:

- warehouses;
- inventory items used for stock control;
- stock receipts;
- stock issues;
- warehouse transfers;
- stocktakes;
- lots / batch numbers;
- expiry dates (`HSD`);
- immutable stock movement ledger;
- current stock balance projection;
- invoice integration inbox;
- invoice-line-to-inventory-item matching decisions;
- inventory audit history;
- Excel audit/export;
- Admin Inventory dashboard and operational workspaces.

The module must support ordinary products and pharmaceutical stock without becoming a second owner of Product, Pharma, Partner, or Invoices master data.

Central invariant:

> **An invoice is documentary evidence, not a stock posting.**
>
> Synchronizing/importing a purchase invoice may create or refresh an Inventory draft receipt proposal, but stock changes only after an authorized operator explicitly confirms the warehouse receipt.

---

# 2. Canonical Ownership Boundary

## 2.1 Inventory owns

Inventory is canonical owner of:

- physical stock quantity;
- warehouse configuration and warehouse operational state;
- inventory-specific stock identity (`InventoryItem` concept);
- receipt/issue/transfer/stocktake documents and lifecycle;
- lot/batch and expiry stock state;
- stock movement ledger;
- stock balance projection;
- inventory-side invoice integration inbox;
- inventory-side matching/alias decisions;
- inventory audit/export.

## 2.2 Invoices owns

`Modules\Invoices` remains canonical owner of:

- GDT / MeInvoice / invoice-source acquisition;
- invoice authentication and synchronization;
- invoice raw/source payload handling;
- invoice normalization;
- local invoice persistence;
- invoice identity and duplicate handling inside the invoice domain;
- invoice PDF generation/retrieval/file metadata;
- invoice PDF storage under:

```text
storage/app/invoices/pdf
```

- invoice-related Excel import/export;
- invoice backup/recovery metadata.

Inventory must not:

- fetch invoices from GDT;
- parse invoice PDFs;
- read `storage/app/invoices/pdf` as a business-data source;
- duplicate invoice PDF files;
- own GDT tokens, lookup codes, XML/PDF metadata, or invoice identity rules;
- mutate Invoices-owned persistence directly except through an explicitly approved integration contract.

Canonical integration direction:

```text
Invoices source/API/PDF
    -> Invoices normalization
    -> Invoices persistence
    -> explicit normalized Inventory integration contract
    -> Inventory integration inbox
    -> draft receipt
    -> product/item matching + warehouse/lot/HSD review
    -> explicit receipt confirmation
    -> stock movement ledger
    -> stock balance projection
```

## 2.3 Product owns

`Modules\Product` remains owner of general product/catalog master data including product title, description, categories, images, pricing, reviews, web/catalog behavior and related product-facing fields.

Inventory may reference a Product but must not become a second Product master.

The existing `Product.quantity` field must not remain an independently authoritative stock source after Inventory becomes canonical for physical stock.

Target compatibility direction:

```text
Inventory stock balance = canonical physical stock
Product.quantity        = compatibility projection/read adapter only, if still required
```

Changing or removing `Product.quantity` requires a separate caller/data/compatibility audit and is not an automatic part of the first Inventory implementation.

## 2.4 Pharma owns

`Modules\Pharma` remains canonical owner of pharmaceutical medicine master/profile, pharmaceutical source intelligence, medicine identity/enrichment, procurement award intelligence and Pharma workspaces.

Inventory owns only physical stock state after a medicine is mapped to an Inventory item.

Inventory may consume approved Pharma fields for matching but must not overwrite Pharma master data from invoice text.

## 2.5 Partner owns

`Modules\Partner` remains canonical owner for suppliers, customers, healthcare organizations and external organizations.

Inventory documents may reference `partner_id`, but Inventory must not automatically create/update Partner master records from invoice data.

For invoice-origin receipts:

```text
normalized seller
    -> match existing Partner when confidently available
    -> otherwise preserve immutable seller snapshot + unresolved Partner state
```

Unresolved Partner identity is allowed for receipt confirmation in the initial release, provided the original seller snapshot and tax code are preserved for audit and the UI makes the unresolved state explicit.

---

# 3. Approved Business Decisions

The following decisions are approved and must be treated as requirements.

## Decision 1 — Standalone InventoryItem is allowed

An `InventoryItem` may exist before it is mapped to Product or Pharma.

Minimum standalone identity requirements:

- unique internal SKU/stock code;
- display name;
- base stock UOM;
- active state;
- tracking flags as applicable.

Product/Pharma mappings may be added later through reviewed matching workflows.

## Decision 2 — Partner resolution is not mandatory to confirm receipt

Receipt confirmation does not require a resolved canonical Partner in v1.

For unresolved invoice supplier:

- preserve seller name snapshot;
- preserve seller tax code when available;
- preserve seller address snapshot when available;
- display unresolved Partner warning;
- allow later Partner resolution without rewriting historical documentary evidence.

## Decision 3 — Negative stock is forbidden in v1

Negative stock is globally disallowed in the first release.

There is no warehouse-level override in v1.

Issue/transfer confirmation must fail when available quantity is insufficient.

## Decision 4 — Physical quantity first; no canonical valuation method in v1

The first release owns physical quantity ledger and operational stock cost evidence, but does not implement canonical inventory accounting valuation such as FIFO cost layers or weighted-average accounting.

Receipt lines may preserve `unit_cost` / source cost for audit and future valuation work.

Accounting valuation is deferred.

## Decision 5 — Stocktake uses one explicit confirmation step

Initial stocktake workflow uses one explicit confirm action protected by a dedicated permission.

No multi-level approval workflow is required in v1.

---

# 4. Actors / Roles

Conceptual actors are capability-based rather than hard-coded business roles.

## Inventory Viewer

May browse:

- dashboard;
- stock balances;
- warehouses/items when permitted;
- lots/HSD;
- operational document lists/details;
- audit data according to permission.

## Inventory Operator

May create/edit draft:

- receipts;
- issues;
- transfers;
- stocktakes;
- invoice line matching decisions according to granted capabilities.

## Inventory Confirmer

May confirm the specific document type for which a confirm permission is granted.

Confirmation is a higher-risk stock mutation boundary and must not be implied by create/edit access.

## Inventory Auditor / Exporter

May inspect audit/history and export approved data scopes.

All sensitive actions require backend authorization. UI visibility alone is not authorization.

---

# 5. Core Domain Concepts

## 5.1 Warehouse

Represents a physical stock-holding warehouse.

Minimum requirements:

- unique code;
- name;
- active/inactive state;
- optional Partner/facility reference;
- address/province metadata as applicable;
- created/updated audit metadata.

Initial scope is warehouse-level stock.

Shelf/bin/location-level control is deferred.

## 5.2 Inventory Item

Inventory-specific stock identity.

Required conceptual fields:

- SKU / stock code;
- display name snapshot;
- base UOM;
- optional `product_id` integration reference;
- optional `pharma_medicine_id` integration reference;
- lot tracking flag;
- expiry tracking flag;
- allow fractional quantity flag;
- reorder threshold when configured;
- active/inactive/blocked state;
- metadata/provenance as applicable.

An Inventory Item must not duplicate Product or Pharma master responsibilities.

## 5.3 Inventory Item Alias / Supplier Alias

Persistent reviewed mapping used to make future invoice matching deterministic.

Conceptual identity may include:

- supplier identity / tax code or Partner reference;
- source product code if available;
- normalized invoice description key;
- UOM/package evidence;
- mapped inventory item;
- provenance and confirmation actor/time.

A confirmed alias may be reused for future deterministic suggestions.

Ambiguous aliases must not be auto-created.

## 5.4 Lot

Lot/batch is first-class when tracking applies.

Lot identity belongs to an inventory item.

`lot_number` must never be globally unique by itself.

Conceptual identity:

```text
inventory_item_id
+ lot_number
(+ expiry_date when needed to disambiguate source data)
```

Lot may retain:

- expiry date;
- manufacture date;
- source receipt line;
- supplier snapshot/reference;
- status/metadata.

## 5.5 Stock Movement

Confirmed physical stock mutation is represented by an immutable movement ledger entry.

Movement is canonical historical truth.

A confirmed movement must not be edited/deleted to repair history.

Corrections use explicit reversal/compensating movements.

## 5.6 Stock Balance

Current balance is a projection/cache of posted movements for efficient reads.

Conceptual balance key:

```text
warehouse_id
inventory_item_id
lot_id nullable
```

Canonical truth remains posted movement history, not an independently mutable balance field.

---

# 6. Quantity and UOM Requirements

Inventory quantities must support decimal precision where appropriate; quantity must not be limited to integer semantics.

Examples include:

- kg;
- litre;
- mL;
- metre;
- package fractions when business rules permit them.

Every Inventory Item has a base stock UOM.

Invoice UOM may differ from base UOM.

Matching/review may establish a controlled conversion, for example:

```text
1 hộp = 30 ống
```

Conversion rules must be item/package-specific or otherwise explicitly scoped. Do not create unsafe global conversions based only on UOM names.

Persisted stock movements must use a stable canonical quantity representation in the item's base stock UOM while preserving documentary/original UOM evidence where needed for audit.

---

# 7. Receipt Workflow

## 7.1 States

```text
DRAFT
  -> CONFIRMED
  -> CANCELLED
```

A draft may be edited until confirmed/cancelled.

A confirmed receipt is immutable as a business document for stock history.

## 7.2 Receipt Sources

Supported initial source types:

- manual;
- invoice;
- controlled import/opening balance when later enabled;
- adjustment/correction as explicitly modeled.

## 7.3 Confirmation Preconditions

Receipt confirmation requires:

- status is still DRAFT;
- caller has `inventory.receipt.confirm` capability;
- selected warehouse exists and is active;
- every stock line has a resolved Inventory Item;
- `UNRESOLVED` stock-relevant lines do not remain;
- quantity is valid and positive;
- required UOM conversion is resolved;
- lot number is present when lot tracking is required;
- expiry is present when expiry tracking is required;
- source duplicate/idempotency checks pass;
- concurrency guard passes;
- posting transaction succeeds atomically.

Resolved Partner is not required in v1.

## 7.4 Receipt Confirmation Side Effects

Within one transaction:

- lock/guard the draft against duplicate confirmation;
- create/reuse approved lot records;
- create deterministic receipt movement(s);
- update balance projection;
- record confirmation actor/time;
- write audit evidence;
- transition receipt to CONFIRMED.

If any step fails, no partial stock posting may remain.

## 7.5 Correction

Confirmed receipt must not be edited in place.

Correction uses explicit reversal/correction document or service workflow that produces compensating movements with linkage to the original document.

---

# 8. Issue Workflow

## 8.1 States

```text
DRAFT
  -> CONFIRMED
  -> CANCELLED
```

## 8.2 Use Cases

Initial issue reasons may include:

- internal consumption;
- department issue;
- customer dispatch;
- controlled wastage/destruction with reason;
- other approved operational reasons.

Sold invoice -> draft issue automation is not part of v1.

## 8.3 Confirmation Preconditions

- caller has `inventory.issue.confirm`;
- warehouse/item/lot references are valid;
- required lot selection is explicit;
- sufficient quantity exists;
- negative stock is denied;
- concurrency guard passes;
- movement posting is atomic.

## 8.4 FEFO

For expiry-tracked pharmaceutical items, the UI/service should support FEFO suggestion as a SHOULD-HAVE capability.

FEFO suggestion does not silently override operator review in v1.

---

# 9. Transfer Workflow

## 9.1 States

```text
DRAFT
  -> CONFIRMED
  -> CANCELLED
```

## 9.2 Initial Transfer Model

V1 uses one-step atomic transfer.

Confirmation creates paired movements:

```text
source warehouse      -> OUT
 destination warehouse -> IN
```

Both sides must post atomically in one transaction.

A transfer must never end CONFIRMED with only one side posted.

Negative stock at the source warehouse is forbidden.

Two-step in-transit/shipping/receiving transfer is FUTURE scope.

---

# 10. Stocktake Workflow

## 10.1 States

```text
DRAFT
  -> COUNTED
  -> CONFIRMED
  -> CANCELLED when still permissible before posting
```

Exact editable transitions must be finalized in CREATE_PLAN while preserving the approved single explicit confirmation requirement.

## 10.2 Stocktake Behavior

Stocktake captures system quantity snapshot and actual counted quantity.

At confirmation:

```text
variance = actual_count - system_balance_at_approved_posting_basis
```

Only the variance creates adjustment movement(s).

Stocktake confirmation requires `inventory.stocktake.confirm`.

No multi-level approval workflow is required in v1.

Historical counted data and confirmed adjustment evidence must remain auditable.

---

# 11. Invoice Integration Requirements

## 11.1 Integration Contract

Inventory receives a normalized invoice DTO/event/application contract from Invoices.

Inventory does not receive a PDF as its business input.

Contract must be versioned.

Suggested conceptual contract semantics:

```text
contract_version
source_module = invoices
invoice_id
invoice_type
invoice_identity_key
lookup_code nullable
invoice_number
invoice_symbol nullable
invoice_template nullable
issued_at
signed_at nullable
currency
seller snapshot
buyer snapshot
totals
source_payload_hash
normalized_payload_hash
pdf_reference nullable opaque reference only
lines[]
```

Each line should expose normalized documentary evidence such as:

```text
source_line_key
line_number
raw_description
normalized_description
uom_raw
uom_normalized nullable
quantity
unit_price
vat_rate nullable
line_subtotal / line_total
lot_number nullable
expiry_date nullable
manufacture_date nullable
manufacturer_name nullable
country_of_origin nullable
source_product_code nullable
source_metadata
```

The exact PHP namespace/DTO shape is implementation planning work, but semantics above are required.

## 11.2 Contract Versioning

- payload includes `contract_version`;
- additive optional fields may remain compatible;
- semantic breaking changes require a new major contract version;
- Inventory must reject unsupported major versions rather than silently interpret them;
- replay of supported versions remains idempotent.

## 11.3 Invocation Model

Initial implementation should prefer an explicit application service integration boundary and durable Inventory inbox over hidden Eloquent observers.

Business-critical integration must not rely on generic model-created/model-updated observers.

Queueing is optional initially; idempotency/inbox semantics are mandatory regardless of synchronous or queued invocation.

## 11.4 Purchase Invoice Eligibility

A normalized purchase invoice may create/update one draft receipt proposal for the approved Inventory purpose.

Flow:

```text
normalized purchase invoice
    -> Inventory integration inbox
    -> draft receipt proposal
    -> classify/match lines
    -> select warehouse
    -> verify quantity/UOM/lot/HSD
    -> confirm receipt
    -> post stock
```

Invoice synchronization by itself must never post stock.

## 11.5 Sold Invoice

Sold invoice must not automatically issue stock in v1.

A future integration may create a draft issue proposal, subject to separate approval.

## 11.6 Non-stock Lines

Invoice lines must explicitly classify as:

```text
STOCK
NON_STOCK
UNRESOLVED
```

`NON_STOCK` is an explicit reviewed classification, not a synonym for failed match.

A receipt cannot confirm while required stock-relevant lines remain `UNRESOLVED`.

---

# 12. Product / Inventory Matching Requirements

## 12.1 Matching Principles

Matching is human-in-the-loop.

Deterministic matches may be suggested and reused after explicit approval.

Ambiguous/weak matches must not be automatically confirmed.

No fuzzy/AI automatic merge is allowed in v1.

## 12.2 Matching Order

Preferred deterministic order:

1. exact existing supplier/item alias;
2. exact source product code/SKU when reliable and scoped;
3. deterministic normalized product/medicine candidate;
4. human review.

## 12.3 Normalization

Documentary attributes such as lot/HSD/manufacture country/package text must be separated from the item identity when possible.

For example:

```text
Biviantac Fort (H/50 viên), lô 041224, HSD: 21/12/2027, NSX: Việt Nam
```

should conceptually normalize into:

```text
item candidate : Biviantac Fort (H/50 viên)
lot            : 041224
expiry         : 2027-12-21
uom            : Viên
country        : Việt Nam
```

A reviewed mapping may persist as an alias so future invoices from the same supplier/source can match deterministically.

## 12.4 Matching Outcomes

At minimum:

```text
MATCHED
UNRESOLVED
NON_STOCK
CONFLICT
```

Exact implementation names may be standardized in CREATE_PLAN.

## 12.5 Master Data Safety

Inventory matching must never automatically overwrite Product or Pharma master fields based on invoice description.

---

# 13. Reference Invoice Fixture

Use the existing invoice PDF as a reference fixture owned by Invoices:

```text
2026-01-04_HD-1_0317953611_cong-ty-tnhh-thuong-mai-duoc-pham-khang-phat.pdf
```

Expected reference facts include:

### Seller

```text
CÔNG TY TNHH THƯƠNG MẠI DƯỢC PHẨM KHANG PHÁT
MST: 0317953611
```

### Buyer

```text
CÔNG TY TNHH INAFO VIỆT NAM
MST: 0314492345
```

### Line 1

```text
Biviantac Fort (H/50 viên)
Lot: 041224
HSD: 2027-12-21
UOM: Viên
Quantity: 61,600
Unit price: 1,800
VAT: 5%
```

### Line 2

```text
Atirin Suspension (Hộp 30 ống x 10ml)
Lot: 5328
HSD: 2027-05-08
UOM: Ống
Quantity: 14,940
Unit price: 5,523.80
VAT: 5%
```

Inventory tests must consume/mock the normalized integration contract, not test PDF parsing.

Invoices tests own parser/PDF normalization behavior.

---

# 14. Idempotency and Duplicate Protection

Idempotency must be enforced through both application logic and database constraints where appropriate.

## 14.1 Integration Inbox Duplicate Protection

The same normalized invoice replay must not create duplicate integration inbox records or duplicate draft receipt proposals.

Conceptual unique identity:

```text
source_module
+ source_invoice_identity
+ integration_purpose
```

Payload hashes may be used to detect unchanged replay versus changed source payload.

## 14.2 Draft Receipt Duplicate Protection

There must be at most one active invoice-origin receipt proposal for the same approved source identity/purpose unless an explicit correction/replacement workflow exists.

## 14.3 Confirmation Duplicate Protection

Two concurrent confirm requests for the same document must not post stock twice.

Use transaction plus row lock/optimistic version/idempotency mechanism as determined in CREATE_PLAN.

## 14.4 Movement Duplicate Protection

Movement posting must use a deterministic source identity/unique constraint concept such as:

```text
document_type
+ document_id
+ document_line_id
+ movement_role
```

Exact schema may vary, but duplicate movement posting must be impossible under retry/double-submit/concurrent request conditions.

## 14.5 Source Changed After Posting

If a normalized invoice changes after its related receipt has already been confirmed:

- Inventory must not silently rewrite the confirmed receipt or stock movements;
- mark source divergence/reconciliation-required state;
- present operator-visible reconciliation path;
- require explicit corrective document/reversal if physical stock must be changed.

---

# 15. Audit Requirements

Inventory is an operational audit-sensitive module.

Audit evidence must identify, as applicable:

- actor;
- action;
- document type/id/number;
- source/integration identity;
- timestamp;
- state transition;
- warehouse/item/lot;
- quantity before/after or explicit movement amount;
- reason/note for correction/cancellation;
- source payload hash/version for invoice-origin work;
- matching decision/provenance;
- reversal linkage.

Confirmed stock ledger entries are immutable.

Do not rely only on generic framework timestamps for high-risk stock confirmation evidence.

---

# 16. Excel / Import / Export Requirements

Inventory must reuse the repository's canonical Shared Import/Export infrastructure where applicable.

## 16.1 Export — MUST HAVE

Support auditable Excel export for appropriate workspaces including:

- current stock balances;
- stock movements;
- receipts;
- issues;
- transfers;
- stocktakes;
- lots/HSD;
- invoice integration/matching audit where useful.

Export semantics for checkbox-enabled lists:

```text
selected_ids not empty -> export exactly selected approved records
selected_ids empty     -> export all records matching current approved export filters
```

Never silently export only the current pagination page when no selection exists.

Exports containing operational stock/partner information must use private/safe delivery according to repository infrastructure.

Large exports must be bounded, chunked/lazy/queued as appropriate.

## 16.2 Direct Ledger/Balance Import — FORBIDDEN

Do not allow generic Excel import directly into:

```text
inventory_movements
inventory_balances
```

Such direct mutation would bypass ledger invariants.

## 16.3 Controlled Import — SHOULD HAVE / scoped

Potential supported imports:

- warehouse master;
- inventory item mapping/master;
- controlled opening balance migration;
- stocktake count sheet.

Opening balance import must create a controlled approved stock posting/initial-balance document, not directly insert balance values.

Import mode, unique keys and templates must be explicitly designed in CREATE_PLAN before implementation.

---

# 17. Admin UI / UX Requirements

All Admin Inventory workspaces must follow `.codex/standards/ADMIN_UI_STANDARD.md` and reuse the canonical Admin shell/shared components.

## 17.1 Canonical Route Family

Target route family:

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

Exact route names are defined in CREATE_PLAN but must remain clearly Inventory-owned.

## 17.2 Dashboard

Dashboard must be an operational workspace, not only a card menu.

Evaluate and provide useful KPIs/action queues including:

- current stock overview;
- active warehouses;
- draft receipts awaiting action;
- invoice lines awaiting matching;
- low-stock items;
- lots approaching expiry;
- stock anomalies;
- incomplete stocktakes;
- deep links into filtered operational workspaces.

## 17.3 List Workspace Standards

Potentially large lists require:

- keyword search;
- domain-relevant filters;
- clear reset filters;
- bounded pagination;
- visible pagination states;
- clear empty/loading/error states;
- responsive overflow handling;
- status badges;
- permission-aware actions;
- row selection when useful for export/bulk non-destructive actions.

Canonical page-size choices:

```text
10 / 25 / 50 / 100
```

No unbounded `All` page-size option.

Changing filters/page-size must reset pagination and selection where scope changes.

## 17.4 Confirmation UX

Stock confirmation is a consequential mutation.

UI must show:

- document and warehouse context;
- count of affected lines;
- unresolved validation issues;
- confirmation warning;
- loading/disabled state to prevent double submit;
- explicit success feedback.

Server-side idempotency remains mandatory even with disabled UI buttons.

## 17.5 Invoice Matching Workspace

Invoice inbox/matching UI must expose:

- invoice identity/header context;
- seller snapshot/Partner resolution state;
- raw + normalized line description;
- UOM/quantity;
- lot/HSD;
- suggested candidates;
- selected Inventory Item;
- `STOCK/NON_STOCK/UNRESOLVED/CONFLICT` state;
- alias/provenance when applicable;
- route back to draft receipt/invoice context.

The UI must not encourage blind bulk acceptance of ambiguous matches.

---

# 18. Permissions

Initial capability set:

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
```

Exact seeding/permission integration is defined in CREATE_PLAN according to current repository conventions.

Rules:

- create/edit does not imply confirm;
- confirm capability is server-side enforced;
- export permission does not depend on delete permission;
- hiding UI controls is not authorization;
- Super Admin behavior follows repository-wide Gate rules.

---

# 19. Proposed Persistence Families

The following are approved conceptual table families; exact columns/indexes/foreign-key strategy are finalized in CREATE_PLAN.

```text
inventory_warehouses
inventory_items
inventory_item_aliases

inventory_receipts
inventory_receipt_lines

inventory_issues
inventory_issue_lines

inventory_transfers
inventory_transfer_lines

inventory_stocktakes
inventory_stocktake_lines

inventory_lots

inventory_movements
inventory_balances

inventory_invoice_inbox
inventory_invoice_inbox_lines

inventory_audit_logs
```

Requirements:

- use MySQL-appropriate decimal types for quantity/money;
- indexes must reflect real filters/business keys;
- unique constraints enforce idempotency/business identity where possible;
- use foreign keys when compatible with module ownership/migration lifecycle;
- do not globally unique lot number;
- no destructive change to existing Product/Invoices/Partner/Pharma schema without separate approval;
- schema must support safe concurrency and deterministic posting.

---

# 20. Bootstrap Contract

Inventory must fit the current first-party `Modules\ModuleServiceProvider` architecture.

```text
Manifest          : config/module.php
Type              : domain
Dependencies      : [] initially unless CREATE_PLAN proves a hard dependency is genuinely required
Module Provider   : not required initially
Config            : yes
Web routes        : yes
API routes        : no in v1
Migrations        : yes
Livewire          : yes
Blade components  : prefer shared; module-specific only when justified
Console commands  : no in v1
Runtime state     : supported
Runtime storage   : private export/temp files only as needed
```

Do not introduce:

- `module.json`;
- nwidart infrastructure;
- a second module registry;
- manual global provider registration;
- duplicate discovery/bootstrap logic.

---

# 21. Cross-Module Dependencies

## 21.1 Manifest Hard Dependencies

Initial target:

```text
depends = []
```

Rationale: Inventory core operations must remain usable for manual receipt/issue/transfer/stocktake/reporting even if optional integration modules are disabled.

A hard dependency may be added only if CREATE_PLAN proves Inventory cannot safely boot/function without that module.

## 21.2 Optional Integration Boundaries

Inventory may integrate with:

- Invoices — normalized purchase invoice contract;
- Product — product master reference/mapping;
- Pharma — medicine master reference/matching enrichment;
- Partner — supplier/customer/facility reference;
- Shared — stable import/export/UI infrastructure;
- Admin/Auth/Role — shell/auth/capability infrastructure according to repository conventions.

Cross-module access must use explicit services/contracts/references and must not bypass canonical ownership.

Disabled optional dependency behavior must be graceful: Inventory core UI should remain functional while unavailable integration-specific actions are hidden/disabled with clear state.

---

# 22. Runtime State Requirements

Inventory supports current centralized runtime module state.

Requirements:

- manifest/default state is tracked source metadata;
- runtime ON/OFF state is resolved through `ModuleStateRepository` / `ModuleStateResolver`;
- Inventory business code must not read/write `storage/app/system/module-state.json` directly;
- runtime toggle must not modify tracked manifest source;
- Git must remain clean after runtime toggles;
- dependency validation follows current ModuleRegistry/Graph behavior.

Default enabled state is to be selected in CREATE_PLAN based on new-module rollout convention, with a preference for safe/controlled rollout rather than silent production activation.

---

# 23. Runtime Storage / Docker Requirements

Inventory does not own invoice PDF storage.

Inventory runtime storage, when required, is limited to controlled private artifacts such as:

- temporary import files;
- generated Excel exports/audit reports;
- queued export artifacts;
- controlled error reports.

CREATE_PLAN must inspect runtime ownership/permissions before implementation if new directories are required.

Requirements:

- use Laravel Storage abstractions;
- keep sensitive operational exports private;
- define retention/cleanup;
- do not use `chmod 777`;
- account for PHP-FPM `www-data` versus root CLI ownership differences;
- do not create runtime directories during analysis/planning merely as a side effect.

---

# 24. Security and Data Integrity Requirements

Mandatory safeguards:

- capability-specific backend authorization;
- validation of every mutation input;
- transactional stock posting;
- duplicate/idempotency protection;
- concurrency protection on confirmations;
- no negative stock in v1;
- no direct browser-provided storage paths/model classes/table names;
- private file delivery for sensitive exports;
- no raw exception text returned to users;
- immutable posted movement history;
- correction through reversal/compensation;
- explicit trust boundary for Invoices normalized payload;
- unsupported integration contract versions fail closed;
- no automatic Product/Pharma/Partner mutation from invoice text;
- audit evidence for consequential mutations.

---

# 25. Search / Filter / Reporting Requirements

At minimum, operational workspaces should evaluate filters such as:

## Stock Balance

- warehouse;
- item/SKU/name;
- Product/Pharma mapping state;
- lot;
- expiry range;
- low-stock state;
- zero/non-zero stock.

## Receipts / Issues / Transfers / Stocktakes

- document number;
- status;
- warehouse;
- partner snapshot/reference when relevant;
- source type;
- date range;
- creator/confirmer where appropriate.

## Lots/HSD

- warehouse;
- item;
- lot number;
- expiry range;
- expired / expiring-soon / valid;
- positive-balance-only.

## Movements

- date range;
- warehouse;
- item;
- lot;
- movement type;
- source document;
- actor.

## Invoice Inbox

- integration status;
- invoice identity;
- supplier/tax code;
- issue date;
- match state;
- draft receipt state;
- source payload changed/reconciliation state.

Dashboard and exports should reuse canonical query/filter services rather than independently reimplementing filter semantics.

---

# 26. Jobs / Events / Notifications

## MUST HAVE

No background job is mandatory for the first usable core if synchronous workloads are safe and bounded.

## SHOULD HAVE

Use queues for long-running exports/imports and high-volume invoice integration if runtime scale requires it.

Any queued integration/post-processing must remain idempotent.

## FUTURE

Potential notifications/alerts:

- expiry approaching;
- low stock;
- integration/matching failures;
- stocktake variance requiring action;
- reconciliation required after invoice source changes.

No recurring scheduler/cloud requirement is approved in v1.

---

# 27. MUST / SHOULD / FUTURE Scope

## MUST HAVE — first usable release

- domain module bootstrap/manifest/runtime compatibility;
- warehouses;
- standalone Inventory Items;
- Product/Pharma optional mappings;
- receipts;
- issues;
- one-step atomic transfers;
- stocktakes with explicit confirm;
- lot/HSD tracking;
- immutable movement ledger;
- stock balance projection;
- purchase invoice normalized integration inbox;
- purchase invoice -> draft receipt only;
- manual/deterministic product matching;
- persistent reviewed aliases;
- non-stock line classification;
- idempotency/duplicate protection;
- concurrency-safe confirmation;
- no negative stock;
- audit trail;
- Excel audit/export;
- Admin dashboard/workspaces;
- capability-specific permissions;
- bounded pagination/search/filters;
- reference normalized fixture from current invoice PDF.

## SHOULD HAVE

- FEFO lot suggestion;
- low-stock thresholds;
- expiry/near-expiry dashboard alerts;
- controlled opening balance import;
- stocktake Excel count template/import;
- advanced invoice reconciliation UI;
- dashboard trends;
- queued large exports/imports where needed.

## FUTURE

- two-step transfer/in-transit workflow;
- shelf/bin/location management;
- serial tracking;
- sold invoice -> draft issue proposal;
- barcode/QR warehouse scanning;
- mobile/PWA warehouse execution;
- automatic FEFO allocation;
- fuzzy/AI matching;
- replenishment planning;
- stock reservations / available-to-promise;
- canonical FIFO/weighted-average accounting valuation;
- advanced cost layers;
- scheduled stock notifications/automation.

---

# 28. Explicit Out of Scope for v1

- PDF parsing inside Inventory;
- moving invoice PDF storage out of Invoices;
- GDT/MeInvoice authentication or synchronization ownership;
- automatic invoice-to-stock posting;
- sold invoice automatic stock issue;
- automatic creation/update of Partner from invoice;
- automatic mutation of Product or Pharma master from invoice;
- fuzzy/AI automatic match confirmation;
- negative stock override;
- accounting valuation engine;
- shelf/bin/location hierarchy;
- serial numbers;
- two-step transfer transit;
- PWA/mobile execution;
- direct movement/balance Excel import;
- destructive refactor of `Product.quantity` without separate audit;
- unrelated changes to Product, Pharma, Partner or Invoices runtime.

---

# 29. Acceptance Criteria

A future implementation is acceptable only when applicable criteria pass.

## Architecture

- Inventory is discovered through the existing root module provider;
- no parallel module infrastructure is introduced;
- ownership boundaries with Invoices/Product/Pharma/Partner are preserved;
- optional integrations do not make unrelated module disablement break Inventory core;
- `MODULE.md`/implementation docs reflect final ownership.

## Invoice Safety

- Inventory never parses invoice PDF;
- current invoice fixture is represented through normalized contract tests;
- same invoice replay does not create duplicate inbox/receipt/movement;
- invoice sync never changes stock without receipt confirmation;
- changed invoice after posted receipt raises reconciliation instead of rewriting history.

## Stock Integrity

- receipt confirmation posts exactly once;
- issue confirmation cannot create negative stock;
- transfer posts both sides atomically;
- stocktake posts only variance;
- confirmed stock movements are immutable;
- corrections create reversal/compensating movements;
- balance equals ledger projection for tested scenarios;
- lot/HSD requirements are enforced for tracked items;
- concurrent confirmation cannot double-post.

## Matching

- exact alias/source-code matches can be suggested deterministically;
- ambiguous lines require review;
- failed match is not silently treated as NON_STOCK;
- reviewed alias can be reused;
- invoice description never automatically overwrites Product/Pharma master.

## UI/UX

- Dashboard loads and exposes actionable operational status;
- search/filter/reset work correctly;
- page sizes are bounded `10/25/50/100`;
- no unbounded `All`;
- desktop/mobile representative widths are usable;
- confirmation actions have clear loading/disabled/confirmation/success states;
- unresolved matching/Partner states are visible;
- no important overflow/404/500/Livewire/browser-console errors.

## Export

- selected rows export only selected rows;
- no selection exports the complete approved filtered dataset, not current page only;
- movement/balance direct import is unavailable;
- generated operational exports use safe/private delivery;
- large datasets are handled in a production-safe manner.

## Runtime State

- default state resolves correctly;
- runtime ON/OFF works according to repository architecture;
- runtime toggle does not edit tracked manifest;
- effective dependency rules remain valid;
- Git remains clean after runtime state operations.

## Verification Strategy

- syntax/lint/Pint for changed PHP scope;
- focused Inventory service/domain tests;
- Inventory module regression;
- Invoices integration contract tests when integration changes;
- Product/Partner/Pharma impacted regression only when their explicit boundaries are changed;
- Admin regression when shared Admin/UI integration is affected;
- System/runtime-state tests when module state integration changes;
- frontend build for UI/assets;
- manual Admin UI smoke;
- full project regression only when justified by final cross-cutting scope/workflow gate.

---

# 30. Approved Decisions Summary

Approved on 2026-09-09:

1. standalone `InventoryItem` is allowed before Product/Pharma mapping;
2. resolved Partner is not mandatory to confirm receipt in v1; immutable seller snapshot is required;
3. negative stock is globally forbidden in v1;
4. v1 owns physical quantity ledger only; no canonical FIFO/weighted-average valuation engine;
5. stocktake uses one explicit confirmation step, not multi-level approval.

These decisions remove the blocking business/schema uncertainties identified during `/analyze-new-module`.

---

# 31. Remaining Non-Blocking Planning Notes

The following are not blockers for `/create-module Inventory`, but CREATE_PLAN must resolve their exact implementation shape before application code:

- exact table column types/precision/indexes and FK strategy;
- exact normalized Invoices DTO/event/service namespace;
- exact Product/Pharma reference strategy under module migration lifecycle;
- exact optimistic-lock vs row-lock design;
- exact lot uniqueness constraint when expiry is missing/ambiguous;
- exact item/UOM conversion persistence model;
- exact reversal document model;
- exact default-enabled rollout state;
- exact Admin route names/menu placement;
- exact Shared Import/Export service/class structure;
- exact Stocktake DRAFT/COUNTED editing transition details;
- migration/compatibility plan for eventual `Product.quantity` de-authoritization.

None of these authorizes destructive changes to existing module data/contracts without a separately reviewed plan.

---

# 32. CREATE-MODULE READINESS

```text
Business requirements : READY
Module boundary        : READY
Bootstrap Contract     : READY
Dependencies           : READY
Database               : READY FOR CREATE_PLAN
Permissions            : READY
Workflow               : READY
Runtime state          : READY
Docker/runtime storage : READY

Overall: READY FOR /create-module Inventory
```

Next task:

```text
/create-module Inventory
```

Business specification input:

```text
docs/modules/Inventory/REQUIREMENTS.md
```

The `/create-module` task must first create/review `docs/modules/Inventory/CREATE_PLAN.md` and stop at its approval gate before any Inventory application/runtime code is generated.
