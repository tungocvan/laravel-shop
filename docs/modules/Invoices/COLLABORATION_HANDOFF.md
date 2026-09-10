# Invoices Collaboration Handoff

## Current Status — Inventory Batch C / Source Classification

- Module: `Invoices`.
- Active branch: `feat/inventory-batch-c-invoice-integration`.
- Current scope: **canonical GDT source data review, business classification, dynamic expense classification and dashboard drill-down toward Inventory intake**.
- User-reported focused automated tests: **PASS**.
- User-reported UI acceptance for Source Data + Dashboard: **PASS**.
- Merge authorization: **NOT YET GIVEN**.

## Canonical Source Data Workspace

Admin workspace:

```text
/admin/invoices/source-data
```

Backward-compatible alias remains:

```text
/invoices/source-data -> /admin/invoices/source-data
```

The workspace reads canonical source data already persisted in `invoice_source_records`. It does not make a GDT recovery/fetch call during classification.

Primary business classification remains:

```text
UNCLASSIFIED
GOODS
SERVICE_EXPENSE
MIXED
```

Supplier-wide rules are scoped by supplier tax code and invoice type. Future source records may inherit a persisted supplier rule. Purchase and sold invoices remain independent scopes.

## Expense Classification — Level 2

`SERVICE_EXPENSE` now supports a dynamic level-2 expense category instead of a hard-coded enum.

Master data table:

```text
invoice_expense_categories
```

Key fields:

```text
id
parent_id
code
name
description
is_active
sort_order
```

Initial categories include:

```text
SERVICE            Dịch vụ
TOOL_EQUIPMENT     Công cụ dụng cụ
FIXED_ASSET        Tài sản cố định
TAX_FEE            Thuế & phí
FINANCE_INTEREST   Lãi & chi phí tài chính
OTHER_EXPENSE      Chi phí khác
```

`invoice_source_records` stores `expense_category_id` plus expense audit fields. Categories can be added later without changing source-record schema. Used category codes should remain stable; obsolete categories should be deactivated instead of deleted where historical reporting depends on them.

The hierarchy via `parent_id` is intentionally extensible for later subcategories and future accounting/cost-center/project mappings.

## Source Classification UI Contract

When the row classification is `SERVICE_EXPENSE`, the workspace exposes:

- dynamic **Phân loại chi phí cấp 2** from active master data;
- optional expense note;
- supplier-wide application when explicitly selected;
- audit metadata for the expense classification.

`GOODS` does not receive expense classification. `MIXED` remains separate and is not force-mapped to one expense category because mixed invoices require line-level classification for an accurate goods/expense split.

Stable Livewire DOM keys for desktop/mobile supplier checkboxes must be preserved to prevent cross-row selection state leakage.

## Invoices Dashboard Integration

`/admin/invoices/dashboard` exposes Source Data as a primary operational workspace and shows classification-aware purchase metrics.

The dashboard separates:

```text
GOODS
SERVICE_EXPENSE
MIXED
UNCLASSIFIED
```

and provides an expense-category breakdown for `SERVICE_EXPENSE`.

Financial classification values use canonical invoice amount before VAT (`amount_before_vat`). VAT is therefore not silently treated as operating expense.

Dashboard drill-down links open Source Data with the matching classification context. Dashboard financial values remain invoice/cost reporting only; they do not represent warehouse stock valuation.

## Inventory Boundary

Classification never increments inventory.

Approved downstream workflow:

```text
GDT
  -> Invoices canonical source data
  -> Admin business classification
  -> eligible purchase invoice
  -> Inventory Inbox
  -> Product Matching
  -> Draft Receipt
  -> Admin warehouse / lot / expiry review
  -> Confirm Receipt
  -> Stock Movement
  -> Stock Balance
```

`GOODS` is an Inventory candidate. `MIXED` requires line-level separation so only goods lines may become receipt lines. `SERVICE_EXPENSE` does not create a normal warehouse receipt. `UNCLASSIFIED` remains blocked for downstream business treatment.

Invoice classification, supplier inheritance, dashboard aggregation and expense classification must not be used as a substitute for Inventory receipt confirmation.

## Validation Evidence

Latest user acceptance recorded for this delivery:

```text
Focused tests: PASS
UI: PASS
```

Validated manually at:

```text
/admin/invoices/source-data
/admin/invoices/dashboard
```

The focused test scope included Source Data workspace contracts and Invoices Dashboard classification/drill-down contracts.

## Previous GDT Smart Sync Delivery

Previous work established secure GDT Smart Sync, canonical database persistence, Excel export, server-side credential handling and PWA/runtime behavior. Those contracts remain in force:

- canonical invoice ownership remains in `Modules/Invoices`;
- GDT credentials/tokens remain server-side;
- database readiness remains authoritative for Smart Date behavior;
- no direct Partner master mutation occurs from invoice import;
- Admin/Invoices remains owner of Google Drive backup/restore and destructive recovery operations.

## Compatibility / Boundaries

This delivery does not:

- rename canonical `/admin/invoices/*` routes;
- move invoice model/schema ownership to Inventory or ClientPortal;
- change Partner master ownership;
- expose GDT secrets to the browser;
- automatically create stock from a classified invoice;
- treat `MIXED` invoices as fully goods or fully expense;
- require schema changes when a new expense category is added to master data.

## Final Closeout Gate

Before merge:

1. preserve the recorded focused test PASS and UI PASS evidence;
2. run only directly impacted Invoices/Inventory checks if further code changes are made;
3. compare the branch against current `main` and resolve base drift if needed;
4. review the final PR against `main`;
5. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
