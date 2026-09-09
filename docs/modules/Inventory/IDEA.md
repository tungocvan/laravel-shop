# Inventory Module — IDEA / Architecture Design

Status: **DESIGN ONLY — no runtime module exists yet**  
Target module: `Modules\Inventory`  
Architecture review date: 2026-09-09

> This document is the design input for a future `analyze-new-module` / `create-module` workflow. It does **not** authorize creation of `Modules/Inventory`, migrations, routes, runtime integrations, menu entries, permissions, or changes to existing modules.

---

## 1. Purpose

`Inventory` is the canonical ERP owner for physical stock operations and inventory accounting state:

- warehouses;
- stock receipts;
- stock issues;
- warehouse transfers;
- stocktakes;
- lots / batch numbers;
- expiry dates (`HSD`);
- stock movement ledger;
- current stock balance;
- invoice-line-to-product matching used by inventory workflows;
- stock audit and Excel export.

The module must support both ordinary products and pharmaceutical stock without turning Inventory into a second Product, Pharma, Partner, or Invoices master-data module.

The central business invariant is:

> **An invoice is documentary evidence, not a stock posting.**
>
> Importing/synchronizing an invoice may create or refresh a **draft receipt proposal**, but stock changes only after an authorized operator explicitly confirms the warehouse receipt.

---

## 2. Architecture principles

1. **Invoices owns invoice ingestion. Inventory owns stock.**
2. **Normalized contract only.** Inventory must not parse invoice PDFs and must not read `storage/app/invoices/pdf` directly.
3. **No auto-posting from invoice ingestion.** Invoice data may create a draft receipt, never a confirmed stock movement.
4. **Human-in-the-loop product matching.** Deterministic matches can be suggested; ambiguous matches require operator confirmation.
5. **Ledger first.** Confirmed inventory changes are represented by immutable stock movements. Balance is a projection/cache of the ledger, not an independent source of truth.
6. **Idempotent integration.** Replaying the same normalized invoice event cannot create duplicate receipts or duplicate stock movements.
7. **Document lifecycle is explicit.** Draft -> confirmed/cancelled; confirmed documents are not silently rewritten.
8. **Lot/expiry are first-class where applicable.** Pharmaceutical receipt/issue must preserve batch and expiry provenance.
9. **No duplicate master data.** Product, Pharma medicine, and Partner remain owned by their canonical modules.
10. **Auditability over convenience.** Every confirmed mutation records actor, document, source, timestamps, and before/after or movement evidence sufficient for later audit.

---

# 3. Canonical ownership boundaries

## 3.1 Invoices vs Inventory

### `Modules\Invoices` owns

Invoices remains the canonical owner for:

- GDT / MeInvoice / invoice-source acquisition;
- invoice authentication/synchronization;
- raw/source invoice payload handling;
- invoice normalization;
- local invoice persistence;
- invoice PDF creation/retrieval/file metadata;
- PDF storage under:

```text
storage/app/invoices/pdf
```

- invoice identity and duplicate handling inside the invoice domain;
- invoice-related Excel import/export;
- invoice backup/recovery metadata.

Inventory must **not**:

- fetch invoices from GDT;
- parse invoice PDF content;
- duplicate invoice PDF files;
- query `storage/app/invoices/pdf` as a data source;
- become owner of invoice numbers, lookup codes, XML/PDF metadata, or GDT tokens.

### `Modules\Inventory` owns

Inventory owns:

- warehouses;
- stock documents and their lifecycle;
- stock receipt/issue/transfer/stocktake lines;
- inventory item mapping required for stock control;
- warehouse/lot quantities;
- inventory stock movement ledger;
- inventory balance projection;
- inventory-side invoice receipt drafts;
- product matching decisions made for inventory use;
- inventory audit/export.

### Integration direction

Canonical direction:

```text
Invoices source/API/PDF
    -> Invoices normalization
    -> Invoices persistence
    -> explicit Inventory integration contract
    -> Inventory invoice import inbox / draft receipt
    -> human review + product/lot/HSD completion
    -> explicit receipt confirmation
    -> stock movement ledger
    -> stock balance projection
```

No reverse dependency from Invoices into Inventory internals is required. Invoices may publish/call an explicit contract; Inventory consumes it.

---

## 3.2 Product boundary

`Modules\Product` remains owner of the general product master and product-facing catalog data.

Inventory must not duplicate canonical product fields such as product marketing title, description, category, images, pricing, reviews, or web/catalog behavior.

Inventory may own an inventory-specific stock identity such as `InventoryItem` because stock control needs fields that are not catalog concerns, for example:

- internal SKU / stock code;
- base stock UOM;
- lot-tracked flag;
- expiry-tracked flag;
- serial-tracked flag reserved for future use;
- reorder threshold;
- preferred warehouse settings;
- inactive/blocked-for-stock state.

`InventoryItem` should reference canonical masters rather than replace them:

```text
inventory_items
- id
- sku
- product_id nullable
- pharma_medicine_id nullable
- display_name_snapshot
- base_uom
- lot_tracking
- expiry_tracking
- is_active
...
```

A future schema analysis must decide whether `product_id` and `pharma_medicine_id` are direct foreign keys or integration references appropriate to the repository's module migration conventions.

### Existing `Product.quantity` concern

The current Product model contains a `quantity` field. Once Inventory becomes canonical for physical stock, `Product.quantity` must **not** remain an independently mutated second stock source.

Future implementation must perform a dedicated compatibility/data audit before changing this behavior. Target direction:

```text
Inventory stock balance = canonical physical stock
Product.quantity        = deprecated compatibility projection/read adapter, if still needed
```

Do not dual-write two authoritative quantities indefinitely.

---

## 3.3 Pharma boundary

`Modules\Pharma` remains canonical owner of:

- pharmaceutical medicine master/profile;
- pharmaceutical source intelligence;
- medicine-specific identity/enrichment;
- procurement award intelligence and Pharma workspaces.

Inventory owns only the physical stock state of a medicine after it is represented as an inventory item.

Recommended relation:

```text
Pharma Medicine
    <-> optional InventoryItem mapping
    -> warehouse stock / lot / HSD / movements
```

Inventory may consume Pharma fields to improve matching, including normalized medicine name, strength, dosage form, registration/manufacturer/package information when the approved Pharma integration contract exposes them.

Inventory must not overwrite Pharma master data based on invoice text.

---

## 3.4 Partner boundary

`Modules\Partner` remains canonical owner for suppliers, customers, healthcare organizations, and external organizations.

Inventory documents may reference `partner_id`, but Inventory must not create/update Partner master records automatically from invoice data.

For invoice-origin receipts:

```text
normalized invoice seller
    -> existing Partner match when confidently available
    -> otherwise preserve seller snapshot + unresolved partner state
    -> optional deep link / candidate workflow to Partner
```

A receipt may remain draft while partner identity is unresolved if business policy allows it. Confirmation policy may require a resolved supplier depending on document type/configuration.

---

# 4. Proposed integration contract: Invoices -> Inventory

## 4.1 Contract goal

Inventory receives a **normalized invoice data object**. It does not receive a PDF as its source of business data.

Suggested conceptual contract name:

```php
InvoiceForInventoryContract
```

or an application DTO/event equivalent owned at the Invoices integration boundary.

The exact namespace/API style must be chosen during repository analysis, but the payload semantics should remain stable.

## 4.2 Header payload

Minimum normalized fields:

```text
contract_version
source_module = invoices
invoice_id                // local Invoices canonical ID when available
invoice_type              // purchase | sold or equivalent canonical direction
invoice_identity_key      // stable Invoices business identity
lookup_code nullable
invoice_number
invoice_symbol nullable
invoice_template nullable
issued_at
signed_at nullable
currency
seller:
  tax_code nullable
  name
  address nullable
  partner_id nullable       // only if already resolved outside Inventory
buyer:
  tax_code nullable
  name
  address nullable
  partner_id nullable
totals:
  subtotal
  tax_total
  grand_total
source_payload_hash
normalized_payload_hash
pdf_reference nullable      // opaque Invoices reference only, not a storage path contract
lines[]
```

## 4.3 Line payload

Each normalized invoice line should expose:

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

Invoices is responsible for extraction/normalization of documentary data. Inventory is responsible for interpreting that normalized line for stock purposes.

## 4.4 Contract versioning

The payload must contain a `contract_version`.

Rules:

- additive optional fields may be backward compatible;
- required semantic changes require a new contract version;
- Inventory must reject unsupported major versions rather than silently misinterpret them;
- replaying an older supported contract remains idempotent.

## 4.5 Invocation model

Initial implementation should prefer an explicit application service call or durable integration inbox over hidden model observers.

Avoid business-critical stock behavior from generic Eloquent `created/updated` observers because it obscures ownership, replay semantics, and failure handling.

Target pattern:

```text
Invoices explicit action
    -> publish/submit normalized invoice to Inventory integration boundary
    -> Inventory upserts integration inbox record
    -> create/update draft receipt proposal when eligible
```

A queued handoff may be introduced later if reliability/throughput requires it, but the inbox/idempotency design should exist even for synchronous invocation.

---

# 5. Invoice eligibility and receipt behavior

## 5.1 Purchase invoice

A normalized **purchase** invoice may create an Inventory receipt proposal.

Expected behavior:

```text
normalized purchase invoice
    -> Inventory integration inbox
    -> one draft receipt for this invoice/business purpose
    -> match each stock-relevant line
    -> operator selects warehouse / verifies quantities / lot / HSD
    -> confirm receipt
    -> post movements
```

## 5.2 Sold invoice

A sold invoice must not automatically issue inventory in the initial design.

Possible future workflow:

```text
sold invoice -> optional draft issue proposal -> explicit confirmation
```

This should be a separate approved integration slice because sales invoicing timing may differ from physical dispatch timing.

## 5.3 Non-stock invoice lines

Service lines, freight, discounts, fees, or other non-stock items must be explicitly classified as:

```text
STOCK
NON_STOCK
UNRESOLVED
```

A receipt cannot confirm while required stock lines remain `UNRESOLVED`.

`NON_STOCK` is an explicit decision, not the result of a failed product match.

---

# 6. Reference fixture — current invoice PDF

Use the existing current invoice PDF as the design/reference fixture:

```text
2026-01-04_HD-1_0317953611_cong-ty-tnhh-thuong-mai-duoc-pham-khang-phat.pdf
```

The PDF remains an **Invoices-owned fixture/reference**. Inventory must consume its normalized representation, not parse the PDF.

Relevant reference values:

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
Biviantac Fort (H/50 viên), lô 041224, HSD: 21/12/2027, NSX: Việt Nam
UOM: Viên
Quantity: 61,600
Unit price: 1,800
VAT: 5%
Lot: 041224
Expiry: 2027-12-21
```

### Line 2

```text
Atirin Suspension (Hộp 30 ống x 10ml) Lô 5328; HSD 08/05/2027; NSX: Việt Nam
UOM: Ống
Quantity: 14,940
Unit price: 5,523.80
VAT: 5%
Lot: 5328
Expiry: 2027-05-08
```

This fixture is especially useful because it exercises:

- medicine/product-name matching;
- package-text normalization;
- line UOM normalization;
- lot extraction;
- expiry extraction;
- pharmaceutical inventory behavior;
- supplier matching through tax code;
- idempotent invoice replay.

Future tests should define expected **normalized contract data** from this invoice. Inventory tests should mock/use the normalized contract rather than asserting PDF parser behavior.

---

# 7. Core domain model

Names below are conceptual and must be validated against repository conventions during implementation analysis.

## 7.1 Warehouses

### `inventory_warehouses`

Proposed fields:

```text
id
code unique
name
partner_id nullable          // if warehouse belongs to a facility/branch
address nullable
province_code nullable
is_active
allow_negative_stock default false
metadata nullable
created_by
updated_by
created_at
updated_at
```

Initial scope is warehouse-level stock. Bin/location management can be introduced later if required.

Possible future tables:

```text
inventory_locations
inventory_bins
```

but they should be deferred unless the current business requires shelf/bin-level control.

---

## 7.2 Inventory items

### `inventory_items`

Purpose: inventory-specific stock identity, not a replacement Product master.

Suggested fields:

```text
id
sku unique
product_id nullable
pharma_medicine_id nullable
display_name_snapshot
base_uom
lot_tracking boolean
expiry_tracking boolean
allow_fractional_quantity boolean
reorder_level nullable
is_active
metadata nullable
created_at
updated_at
```

Important invariants:

- at least one explicit canonical mapping or approved standalone inventory identity is required before stock posting;
- mapping to Product/Pharma is reviewed, not inferred forever from text;
- one canonical item should not be duplicated merely because invoice descriptions vary.

---

## 7.3 Lots

### `inventory_lots`

Suggested identity:

```text
inventory_item_id
lot_number
expiry_date nullable
manufacture_date nullable
```

Additional fields:

```text
supplier_partner_id nullable
source_receipt_line_id nullable
status
metadata
```

A database unique strategy must account for the fact that the same lot number may exist across different products, while an item's lot may need an expiry disambiguator depending on source quality.

Do not globally unique `lot_number` alone.

---

# 8. Stock documents

## 8.1 Receipt

Tables:

```text
inventory_receipts
inventory_receipt_lines
```

Header concept:

```text
id
receipt_no
status: draft | confirmed | cancelled
warehouse_id
supplier_partner_id nullable
source_type: manual | invoice | import | adjustment
source_invoice_id nullable
source_identity_key nullable
source_payload_hash nullable
receipt_date
document_date nullable
note nullable
created_by
confirmed_by nullable
confirmed_at nullable
cancelled_by nullable
cancelled_at nullable
lock_version / concurrency token if needed
```

Line concept:

```text
receipt_id
source_line_key nullable
inventory_item_id nullable
match_status
raw_description_snapshot
uom
quantity
unit_cost nullable
lot_number nullable
expiry_date nullable
manufacture_date nullable
non_stock boolean
note
```

Confirmation prerequisites:

- document is still draft;
- warehouse active;
- quantity valid;
- all required stock lines resolved;
- lot/expiry present when item requires them;
- authorization passes;
- source duplicate checks pass;
- stock posting transaction succeeds atomically.

---

## 8.2 Issue

Tables:

```text
inventory_issues
inventory_issue_lines
```

Use cases:

- internal consumption;
- customer dispatch;
- department issue;
- wastage/destruction with controlled reason;
- future sold-invoice proposal.

Issue confirmation must select the actual lot(s) where lot tracking applies.

Default pharmaceutical issue strategy may suggest **FEFO** (first expiry, first out), but the operator must see/confirm the selected lot unless business rules later authorize automatic allocation.

Negative stock is denied by default.

---

## 8.3 Transfer

Tables:

```text
inventory_transfers
inventory_transfer_lines
```

A transfer is a single business document producing paired movements:

```text
warehouse A: OUT
warehouse B: IN
```

The transaction must post both sides atomically. There must never be a confirmed transfer with only one side posted.

Possible future two-step transit flow (`dispatched -> received`) may be added for geographically separated warehouses, but initial scope can use atomic transfer confirmation if business operations permit.

---

## 8.4 Stocktake

Tables:

```text
inventory_stocktakes
inventory_stocktake_lines
```

Lifecycle:

```text
DRAFT
-> COUNTING
-> REVIEW
-> CONFIRMED
```

Stocktake records:

```text
warehouse
snapshot/cutoff time
item/lot
system quantity at snapshot
counted quantity
difference
reason/note
counter/reviewer
```

Confirmation creates explicit adjustment movements; it must never directly overwrite balance rows without ledger evidence.

---

# 9. Stock movement ledger and balance

## 9.1 Movement ledger

### `inventory_stock_movements`

This is the canonical inventory transaction ledger.

Suggested fields:

```text
id
posting_key unique
occurred_at
warehouse_id
inventory_item_id
lot_id nullable
movement_type
quantity_delta
unit_cost nullable
document_type
document_id
document_line_id
source_module nullable
source_identity_key nullable
actor_id
metadata nullable
created_at
```

Movement types could include:

```text
RECEIPT
ISSUE
TRANSFER_OUT
TRANSFER_IN
STOCKTAKE_GAIN
STOCKTAKE_LOSS
ADJUSTMENT_IN
ADJUSTMENT_OUT
REVERSAL
```

Confirmed movement rows are immutable. Corrections use reversal/replacement documents rather than destructive edits.

## 9.2 Balance projection

### `inventory_stock_balances`

Suggested grain:

```text
warehouse_id
inventory_item_id
lot_id nullable
```

Fields:

```text
quantity_on_hand
quantity_reserved default 0   // reserved for future order allocation
quantity_available
last_movement_id
updated_at
```

`quantity_on_hand` is maintained transactionally with movement posting or rebuilt from the ledger when required.

Unique key must prevent duplicate balance rows for the same stock grain.

## 9.3 Costing

Initial design should avoid overloading the first module delivery with full accounting valuation unless required.

Receipt lines should preserve `unit_cost`. Later valuation methods may include:

- weighted average;
- FIFO;
- lot cost.

The stock quantity ledger must be correct independently of a future accounting valuation subsystem.

---

# 10. Product matching for invoice lines

## 10.1 Why matching must be separate

Invoice descriptions are supplier-authored documentary text. They are not canonical ERP product identifiers.

Example:

```text
Atirin Suspension (Hộp 30 ống x 10ml) Lô 5328; HSD 08/05/2027; NSX: Việt Nam
```

The product identity is mixed with packaging, lot, expiry, and origin data. Matching must separate those concepts before comparison.

## 10.2 Matching pipeline

Recommended deterministic pipeline:

```text
1. exact persistent supplier/product mapping
2. exact source product code mapping, when source code exists
3. exact canonical SKU / barcode / registration identifier, when available
4. exact normalized product alias
5. deterministic Pharma medicine identity match
6. normalized name + package/UOM signals
7. ranked suggestion requiring human confirmation
8. unresolved
```

No fuzzy/AI match may auto-confirm a canonical product in the initial version.

## 10.3 Normalization

Normalization may derive comparison tokens such as:

```text
raw_description
product_name_candidate
package_candidate
uom_candidate
strength_candidate
dosage_form_candidate
lot_candidate
expiry_candidate
manufacturer_candidate
```

Rules:

- lowercase/case-fold for comparison while preserving raw text;
- Unicode normalization;
- collapse punctuation/whitespace;
- normalize common UOM aliases through an approved UOM dictionary;
- strip recognized lot/HSD/manufacture-country fragments from product-name candidate;
- do not strip medically meaningful dosage/strength information;
- preserve every transformation result for explainability/debugging.

## 10.4 Persistent mappings

Proposed table:

```text
inventory_product_match_rules
```

or equivalent mapping entities.

Possible identity:

```text
supplier_partner_id nullable
supplier_tax_code_snapshot nullable
source_product_code nullable
normalized_description_hash nullable
inventory_item_id
match_method
confidence_class
confirmed_by
confirmed_at
is_active
```

A human-confirmed supplier-specific mapping should make future invoices deterministic and fast.

Example:

```text
Supplier 0317953611
+ normalized alias "biviantac fort h 50 vien"
-> InventoryItem #...
```

## 10.5 Match states

Each invoice-receipt line should use an explicit state:

```text
UNMATCHED
SUGGESTED
MATCHED_AUTO_DETERMINISTIC
MATCHED_CONFIRMED
NON_STOCK
CONFLICT
```

The UI must explain why a suggestion exists, e.g.:

```text
Exact supplier alias
Exact product code
Exact Pharma registration
Name + package exact
Name-only candidate (manual review required)
```

## 10.6 Product creation

Inventory must **not** silently create Product or Pharma records from invoice text.

If no canonical master exists, UI may provide:

- mark non-stock;
- map to an existing item;
- create a standalone InventoryItem when policy permits;
- deep-link to Product/Pharma creation/review workflow;
- return later and complete matching.

Creation of canonical Product/Pharma data remains owned by those modules.

---

# 11. UOM and package conversion

Invoice UOM and base inventory UOM may differ.

Example:

```text
invoice description: Hộp 30 ống x 10ml
invoice UOM: Ống
inventory base UOM: Ống
```

or potentially:

```text
invoice UOM: Hộp
inventory base UOM: Ống
conversion: 1 Hộp = 30 Ống
```

Inventory should eventually support explicit conversion rules, but must not infer arbitrary pack conversions from free text and auto-post them without review.

Suggested future mapping:

```text
inventory_item_uom_conversions
- inventory_item_id
- from_uom
- to_base_factor
- source / provenance
- confirmed_by
```

Initial confirmation UI must show both source quantity/UOM and resulting base-stock quantity.

---

# 12. Lot and HSD behavior

Lot (`lô`) and HSD are stock attributes, not merely invoice-note text.

Rules:

- if the matched InventoryItem has `lot_tracking=true`, receipt confirmation requires lot number;
- if `expiry_tracking=true`, receipt confirmation requires valid expiry date unless an explicit privileged exception policy exists;
- HSD cannot precede receipt date without a warning/block policy;
- issue UI should display nearest expiry and suggest FEFO;
- expired lots must be visually distinguished and blocked from ordinary issue unless a specialized authorized disposal/exception flow exists;
- transfer preserves lot identity and expiry;
- stocktake counts at item+lot grain where lot tracking applies.

Dashboard expiry windows should be configurable or at minimum support common views:

```text
Expired
<= 30 days
31–90 days
91–180 days
> 180 days
```

---

# 13. Idempotency and duplicate protection

Duplicate protection is mandatory at several layers.

## 13.1 Invoice integration inbox

Proposed table:

```text
inventory_invoice_integrations
```

Identity:

```text
source_module = invoices
invoice_identity_key
contract_version major
```

Fields:

```text
invoice_id
payload_hash
first_received_at
last_received_at
receipt_id nullable
processing_status
error_code nullable
error_message nullable
```

A repeated identical payload updates `last_received_at` and performs no duplicate creation.

## 13.2 Draft receipt identity

For invoice-created purchase receipts, enforce one logical receipt per source invoice purpose:

```text
(source_module, source_identity_key, document_purpose)
```

Example:

```text
(invoices, <invoice-key>, PURCHASE_RECEIPT)
```

This should be backed by a database uniqueness strategy, not application checks alone.

## 13.3 Draft refresh behavior

When the same invoice is replayed:

### Same hash

```text
No business change.
No duplicate receipt.
No duplicate lines.
```

### Changed normalized hash + receipt still draft

```text
Refresh source-derived draft fields carefully.
Preserve explicit operator decisions/mappings where still valid.
Flag changed lines for review.
Never silently overwrite operator-entered warehouse/lot corrections without a defined merge rule.
```

### Changed normalized hash + receipt already confirmed

```text
Do not mutate confirmed receipt or movements.
Mark integration as SOURCE_CHANGED_AFTER_POSTING / RECONCILIATION_REQUIRED.
Require explicit reconciliation/correction workflow.
```

## 13.4 Posting idempotency

Each movement gets a deterministic unique `posting_key`, conceptually based on:

```text
document_type + document_id + line_id + lot/allocation + movement_leg
```

Re-running confirmation after timeout/retry cannot double-post stock.

## 13.5 Transaction/concurrency

Document confirmation must run in a database transaction and lock/revalidate the document status.

Pattern:

```text
BEGIN
lock draft document
re-check status/permissions/preconditions
create movement rows with unique posting keys
upsert/lock balance rows
apply deltas
mark document confirmed
COMMIT
```

Two concurrent confirmation attempts must yield one successful posting only.

## 13.6 Duplicate manual documents

Manual receipt/issue import should support a user-visible duplicate reference warning based on configurable external reference + partner + date, but warnings are not equivalent to the hard invoice-source uniqueness contract.

---

# 14. Cancellation and correction

Confirmed stock documents must not be deleted to "fix" stock.

Recommended rules:

- draft documents can be edited/cancelled;
- confirmed documents are immutable in stock-affecting fields;
- correction creates a reversal or adjustment referencing the original document;
- reversal produces opposite stock movements;
- every correction records reason and actor;
- hard delete of confirmed movement evidence is forbidden in normal UI.

---

# 15. Audit design

Inventory must provide auditable evidence for:

- receipt creation/source;
- invoice integration/replay;
- line product match decisions;
- warehouse changes before confirmation;
- lot/HSD changes;
- confirmation/cancellation/reversal;
- issue/transfer/stocktake confirmation;
- stock movement creation;
- export execution if repository conventions support export audit logs.

A dedicated domain audit table may be useful for significant state transitions, even if the repository also has a generic audit facility.

Conceptual table:

```text
inventory_audit_events
- event_type
- aggregate_type
- aggregate_id
- actor_id
- source_module nullable
- source_identity_key nullable
- before_payload nullable
- after_payload nullable
- reason nullable
- created_at
```

Sensitive/unbounded payloads should not be copied blindly into audit JSON. Store business-relevant snapshots/hashes and references.

---

# 16. Excel audit / export

Use repository canonical shared import/export infrastructure where applicable:

```text
Modules/Shared/Services/ImportExport
Modules/Shared/Livewire/ImportExport
shared.import-export.panel
```

and follow the repository export semantics:

```text
selected_ids not empty -> export exactly selected rows
selected_ids empty     -> export all records matching approved active filters
```

Never interpret "no selection" as current page only.

## 16.1 Export workbooks

Recommended exports:

### Stock Balance Export

Sheets:

```text
README
BALANCES
LOTS_EXPIRY
FILTERS
```

Columns should include:

```text
warehouse code/name
SKU
canonical item/product name
Product ID / Pharma medicine reference where appropriate
lot
expiry
base UOM
on hand
reserved
available
last movement time
```

### Stock Movement Audit Export

Sheets:

```text
README
MOVEMENTS
DOCUMENTS
FILTERS
```

Movement fields:

```text
movement id
posting key
time
warehouse
item
lot
movement type
quantity delta
unit cost if authorized
document type/number
source
actor
```

### Receipt Audit Export

Sheets:

```text
README
RECEIPTS
RECEIPT_LINES
MATCH_DECISIONS
INVOICE_SOURCE
FILTERS
```

This export should allow tracing:

```text
Invoice identity -> Draft Receipt -> Match Decision -> Confirmed Receipt -> Stock Movements
```

## 16.2 Export safety

- bounded query/stream/chunk strategy for large datasets;
- authorization checked server-side;
- monetary/cost fields can require a stronger permission than quantity-only stock view;
- no GDT credentials/tokens;
- no private PDF filesystem paths;
- no arbitrary raw external payload dump by default;
- dates/times include timezone/format documentation in README;
- spreadsheet values must preserve IDs/codes with leading zeroes as text where relevant.

## 16.3 Import

Initial Inventory scope should support Excel **export/audit** first.

Generic stock import is high risk because it can bypass physical-document workflows. If later introduced, import should stage draft documents and require review/confirmation rather than mutate balances directly.

---

# 17. Dashboard design

Canonical future entry:

```text
/admin/inventory/dashboard
```

The Dashboard is operational and read-oriented; stock mutation happens in dedicated workspaces.

## 17.1 KPI row

Recommended KPIs:

```text
Total active warehouses
Total inventory items with stock
Total on-hand quantity / distinct stocked items
Draft invoice receipts pending review
Unmatched invoice lines
Expired lots
Lots expiring <= 90 days
Low-stock items
Open stocktakes
```

Avoid presenting one meaningless aggregated quantity across incompatible UOMs as a financial/physical "total". Prefer distinct-item counts and domain-safe metrics.

## 17.2 Operational queues

Dashboard sections:

```text
Invoice receipts awaiting review
Product matching conflicts
Expiring/expired lots
Low-stock items
Recent confirmed receipts/issues/transfers
Open stocktakes
Integration errors/reconciliation required
```

Each card/table is a deep link to a filtered canonical workspace rather than a duplicate mini-application.

## 17.3 Trends

Useful chart candidates only when supported by real data:

- receipts vs issues over time;
- top movement items;
- expiry exposure by month;
- warehouse movement volume.

Charts are secondary to actionable queues.

---

# 18. Admin UI/UX design

All Inventory Admin UI must follow:

```text
.codex/standards/ADMIN_UI_STANDARD.md
```

Use canonical Admin shell/layout and approved shared components. Do not invent a competing Inventory design system.

## 18.1 Workspace map

Proposed navigation:

```text
Inventory
├── Tổng quan
├── Tồn kho
├── Nhập kho
├── Xuất kho
├── Chuyển kho
├── Kiểm kê
├── Lô & HSD
├── Đối chiếu sản phẩm hóa đơn
├── Kho hàng
└── Nhật ký / Audit
```

## 18.2 Receipt from invoice UX

Recommended desktop workspace:

```text
Header: Nhập kho từ hóa đơn
Context strip:
  Invoice # / date / supplier / invoice total / integration status

Main workspace
├── Left/main: receipt lines table
│   - invoice description
│   - suggested product
│   - match state
│   - source UOM/qty
│   - base UOM/qty
│   - lot
│   - HSD
│   - warnings
└── Right/secondary drawer/panel
    - warehouse
    - supplier resolution
    - source invoice metadata
    - receipt note
    - confirmation readiness

Sticky/footer actions
- Save draft
- Validate
- Confirm receipt
```

Mobile/tablet:

- collapse side metadata into drawer/tabs;
- each line becomes an expandable card or horizontally safe row;
- product matching opens a focused picker/modal;
- critical lot/HSD warnings remain visible;
- confirmation summary shows unresolved-line count before allowing mutation.

## 18.3 Product matching UX

Product picker should show:

```text
Invoice line raw text
Parsed candidate fields
Suggested matches with reason
Product / Pharma identity
SKU
base UOM
lot/HSD requirements
```

Actions:

```text
Use this item
Search another item
Mark non-stock
Save supplier mapping (permission-aware)
Open Product/Pharma record
```

Do not hide ambiguous confidence behind a generic green "matched" state.

## 18.4 Stock balance list

Filters:

```text
keyword / SKU / product
warehouse
stock state: positive / zero / low / negative anomaly
lot
expiry range/state
Product/Pharma type if useful
page size 10/25/50/100
```

No unbounded `All` pagination option.

Columns should be responsive and prioritize:

```text
Item
Warehouse
Lot / HSD
On hand
Available
UOM
Last movement
```

## 18.5 Documents lists

Receipt/issue/transfer/stocktake lists should include:

- bounded pagination;
- search;
- status filter;
- warehouse filter;
- partner filter where relevant;
- date range;
- source filter (`manual`, `invoice`, etc.);
- reset filter;
- checkbox selection where export applies;
- selected-vs-all-filtered export semantics.

## 18.6 Form controls and state

Must follow repository standard:

- visible boundaries for all inputs/selects;
- searchable select for Product/Partner/Warehouse when datasets are large;
- clear validation adjacent to fields;
- disabled/loading state for confirmation/posting;
- double-submit protection;
- modal confirmation for irreversible stock posting;
- explicit count/scope in bulk actions;
- empty/loading/error states;
- canonical white/indigo bounded pagination.

---

# 19. Authorization model

Suggested permissions are capability-based rather than one broad `manage_inventory` permission.

Conceptual permissions:

```text
inventory.dashboard.view
inventory.stock.view
inventory.cost.view
inventory.warehouse.manage
inventory.receipt.view
inventory.receipt.create
inventory.receipt.edit
inventory.receipt.confirm
inventory.issue.view
inventory.issue.create
inventory.issue.edit
inventory.issue.confirm
inventory.transfer.view
inventory.transfer.create
inventory.transfer.confirm
inventory.stocktake.view
inventory.stocktake.create
inventory.stocktake.confirm
inventory.match.view
inventory.match.resolve
inventory.audit.view
inventory.export
inventory.adjustment.create
inventory.adjustment.confirm
```

Confirmation permissions should be separable from creation/edit permissions for segregation of duties.

Server-side authorization is mandatory; UI visibility alone is not authorization.

---

# 20. Query boundaries

Each major list should have one canonical query/filter owner reused by:

- UI list;
- dashboard deep link;
- export;
- counts/KPI where semantic equivalence is intended.

Avoid slightly different filters in Livewire, export classes, and dashboard services because this causes audit discrepancies.

Potential query services:

```text
StockBalanceQuery
StockMovementQuery
ReceiptQuery
IssueQuery
TransferQuery
StocktakeQuery
InvoiceReceiptInboxQuery
ProductMatchQuery
```

Livewire owns UI state; services/actions own business behavior; Blade does not query DB.

---

# 21. Service/action boundaries

Conceptual application services/actions:

```text
ConsumeNormalizedInvoice
CreateOrRefreshInvoiceReceiptDraft
ResolveInvoiceLineProduct
ConfirmReceipt
ConfirmIssue
ConfirmTransfer
StartStocktake
ConfirmStocktake
PostStockMovements
RebuildStockBalances
ReverseStockDocument
ExportInventoryAudit
```

The exact class architecture should follow repository conventions found during `analyze-new-module`.

Critical stock posting logic should not live in controllers, Blade, or Livewire components.

---

# 22. Error and recovery states

Integration/document states should be operationally visible.

Examples:

```text
RECEIVED
DRAFT_CREATED
REVIEW_REQUIRED
MATCH_CONFLICT
READY_TO_CONFIRM
CONFIRMED
SOURCE_CHANGED_AFTER_POSTING
RECONCILIATION_REQUIRED
FAILED
```

Failures should retain enough context to retry safely without creating duplicate receipts/movements.

A retry action must call the same idempotent boundary, not a separate "force create" code path.

---

# 23. Observability

Future implementation should record/log:

- integration attempts and result state;
- receipt/issue/transfer/stocktake confirmation outcome;
- idempotency duplicate suppression;
- movement posting failures;
- balance rebuild discrepancies;
- source-changed-after-confirmation conditions;
- product match conflicts.

Do not log invoice credentials, protected tokens, or unnecessary personal/sensitive source payloads.

---

# 24. Data integrity invariants

At minimum:

1. confirmed documents cannot be confirmed twice;
2. one invoice purchase purpose cannot create multiple logical receipts;
3. duplicate posting keys are rejected by DB constraint;
4. balance grain is unique;
5. transfer posts both warehouse legs atomically;
6. stocktake confirmation creates adjustment movements rather than direct balance overwrite;
7. issue cannot make stock negative unless warehouse/item policy explicitly permits and permission allows;
8. lot-required item cannot post without lot;
9. expiry-required item cannot post without expiry unless explicit exception policy exists;
10. confirmed stock movement rows are immutable;
11. source invoice replay after posting never silently changes posted stock;
12. deleting Product/Pharma/Partner master data must not orphan historical stock evidence; historical snapshots/restrict/nullability policy must be designed explicitly.

---

# 25. Proposed schema inventory

A likely initial schema set for analysis:

```text
inventory_warehouses
inventory_items
inventory_lots
inventory_receipts
inventory_receipt_lines
inventory_issues
inventory_issue_lines
inventory_transfers
inventory_transfer_lines
inventory_stocktakes
inventory_stocktake_lines
inventory_stock_movements
inventory_stock_balances
inventory_invoice_integrations
inventory_product_match_rules
inventory_audit_events
```

Optional/deferred:

```text
inventory_item_uom_conversions
inventory_locations
inventory_bins
inventory_reservations
inventory_cost_layers
inventory_serials
```

The future analysis must avoid generating all optional tables merely because they are listed here.

---

# 26. Initial delivery scope recommendation

## Phase A — Foundation + stock ledger

- module contract/manifest;
- warehouses;
- inventory items;
- lots/HSD;
- movements;
- balances;
- permissions;
- audit baseline.

## Phase B — Receipt workflow + invoice integration

- normalized Invoices integration contract;
- integration inbox;
- invoice purchase draft receipt;
- product matching;
- lot/HSD review;
- explicit receipt confirmation;
- sample invoice fixture contract tests;
- duplicate/replay protection.

## Phase C — Issue + transfer

- issue workflow;
- FEFO suggestions;
- transfer atomic posting.

## Phase D — Stocktake

- count snapshot;
- variance review;
- adjustment posting.

## Phase E — Dashboard + audit/export closeout

- dashboard;
- stock balance workspace;
- movements/audit;
- Excel exports;
- responsive Admin UI acceptance.

The implementation may combine phases into fewer coherent MRs to reduce repeated pull/test cycles, provided each MR remains reviewable and rollback-safe.

---

# 27. Future tests / acceptance contract

## 27.1 Invoice integration tests

Required cases:

- purchase invoice creates exactly one draft receipt;
- replay identical invoice creates no duplicate;
- changed source while draft refreshes safely and flags changed lines;
- changed source after confirmation creates reconciliation state, not stock mutation;
- sold invoice does not auto-create confirmed issue;
- Inventory never needs PDF parser/filesystem path to process contract.

## 27.2 Matching tests

Using the reference invoice:

- `Biviantac Fort` normalized line preserves lot `041224` and expiry `2027-12-21`;
- `Atirin Suspension` preserves lot `5328` and expiry `2027-05-08`;
- exact confirmed supplier alias becomes deterministic;
- ambiguous name match remains review-required;
- unmatched line blocks receipt confirmation unless marked non-stock;
- mapping does not mutate Product/Pharma master.

## 27.3 Posting tests

- receipt confirmation posts one movement per stock allocation;
- double confirmation is idempotently blocked;
- concurrent confirmation cannot double-post;
- issue checks stock availability;
- transfer posts OUT+IN atomically;
- stocktake posts adjustment deltas;
- movement/balance totals reconcile.

## 27.4 Authorization tests

- view permission cannot confirm;
- creator without confirm permission cannot post;
- cost values hidden without cost permission if that split is adopted;
- export is server-authorized;
- bulk/selected actions do not depend on delete permission.

## 27.5 UI tests

- canonical Admin layout;
- visible input borders/focus/errors;
- bounded pagination only (`10/25/50/100` unless analysis approves another bounded set);
- inactive pagination white, active indigo;
- search/filter reset;
- mobile/tablet/desktop responsive acceptance;
- loading/disabled confirmation state;
- no duplicate-submit behavior;
- selected export vs all-filtered export semantics.

---

# 28. Cross-module regression scope for future implementation

Expected focused regression:

```text
Inventory
Invoices (integration contract only)
Product (only when product mapping boundary changes)
Partner (only when partner resolution/candidate boundary changes)
Pharma (only when medicine integration boundary changes)
Admin (when menu/layout/permissions/Admin surfaces change)
```

Full project regression is not automatically required under the repository workflow.

---

# 29. Explicit non-goals for initial Inventory module

Not part of initial scope unless separately approved:

- PDF parsing/OCR;
- GDT invoice synchronization;
- direct mutation of Product/Pharma/Partner master data from invoice lines;
- automatic invoice-to-stock posting;
- autonomous fuzzy/AI product matching;
- accounting general ledger;
- purchase-order/procurement workflow;
- sales order fulfillment;
- reservation/ATP engine;
- serial-number tracking;
- bin/rack optimization;
- barcode/mobile scanner PWA;
- multi-step warehouse transit;
- automated recall management;
- full inventory valuation accounting;
- unrestricted stock Excel import.

These can be added later through explicit contracts rather than preloading the initial module with speculative complexity.

---

# 30. Architecture decisions to carry into `MODULE.md`

When `Modules\Inventory` is eventually created, its canonical `MODULE.md` should explicitly record:

1. Inventory owns physical stock state, warehouses, stock documents, movements, balances, lots/HSD.
2. Invoices owns invoice ingestion, normalization, persistence, PDF/file storage and source integrations.
3. Inventory consumes a versioned normalized invoice contract and never parses invoice PDFs.
4. Invoice ingestion creates at most a draft receipt proposal; only explicit receipt confirmation changes stock.
5. Product/Pharma/Partner remain canonical masters; Inventory only references/maps them.
6. Stock movement ledger is canonical; balance is derived/projected state.
7. Confirmed stock postings are immutable and corrected by reversal/adjustment.
8. Idempotency is enforced with database-backed source/document/posting uniqueness.
9. Product matching is deterministic/human-reviewed; fuzzy/AI suggestions never auto-post initially.
10. Admin UI follows `.codex/standards/ADMIN_UI_STANDARD.md`.
11. Excel export follows selected-vs-all-filtered semantics and must remain auditable.
12. `Product.quantity` cannot remain a second independent canonical inventory balance after Inventory becomes authoritative; compatibility requires a separate audited migration plan.

---

# 31. Recommended next workflow step

After this IDEA is approved, use:

```text
.codex/tasks/analyze-new-module.md
Input: docs/modules/Inventory/IDEA.md
```

The analysis should inspect repository reality before creating runtime code, especially:

- current module skeleton conventions;
- Product schema and `quantity` callers;
- Invoices normalized data/services and invoice identity rules;
- current Invoices PDF/file behavior;
- Partner integration contracts;
- Pharma medicine identity fields;
- shared Admin components;
- shared import/export infrastructure;
- permission/menu seed conventions;
- migration/module enablement conventions;
- tests and cross-module caller boundaries.

Only after that analysis is approved should `.codex/tasks/create-module.md` be used to create `Modules\Inventory`.
