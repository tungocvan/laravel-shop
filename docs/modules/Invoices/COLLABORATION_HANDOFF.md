# Invoices Collaboration Handoff

## Current Status — Inventory Batch C / Source Classification

- Module: `Invoices`.
- Active branch: `feat/inventory-batch-c-invoice-integration`.
- Current scope: **canonical GDT source data review, business classification, dynamic expense classification, source-detail Excel export, GDT detail recovery and dashboard drill-down toward Inventory intake**.
- User-reported focused automated tests: **PASS**.
- User-reported UI acceptance for Source Data + Dashboard + source-detail export flow: **PASS**.
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

For sold invoices, when a concrete year/month is selected and the current scope still contains `UNCLASSIFIED` rows, the UI exposes a bulk action to classify only those unclassified sold invoices as `GOODS`. Existing explicit classifications are preserved. The bulk-action section is hidden once the unclassified count reaches zero.

## Expense Classification — Level 2

`SERVICE_EXPENSE` supports a dynamic level-2 expense category instead of a hard-coded enum.

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

The existing supplier checkbox under **Phân loại** is reserved for supplier-wide classification and must not be repurposed as an export selector.

## Source Detail Excel Export

Source Data supports exporting either explicitly selected invoices or the entire current filtered result set.

Selection contract:

```text
No invoice checkbox selected -> export all rows matching current filters
One or more invoice checkboxes selected -> export only those selected source invoices
```

Export selection checkboxes are placed directly in the **Hóa đơn** column. The desktop table header also exposes **Chọn tất cả trang**. Selection state is independent from the supplier-wide classification checkbox.

Current export filters include the Source Data context:

```text
year
month
invoice_type
partner
search
detail_status
business_classification
```

The Excel output keeps exactly the approved 20-column layout:

```text
Loại hóa đơn
Mã tra cứu
Ký hiệu
Số hóa đơn
Loại / tên hóa đơn
Ngày lập
Mã số thuế đối tác
Đơn vị / đối tác
Địa chỉ
Email
Số điện thoại
Tiền VAT
Tiền trước VAT
Tổng thanh toán
Chi tiết - ten
Chi tiết - dgia
Chi tiết - dvtinh
Chi tiết - ltsuat
Chi tiết - sluong
Chi tiết - thtien
```

Each canonical `detail_payload['hdhhdvu']` item becomes one Excel row and the invoice-level fields are repeated for each detail line. Detail rows with `thtien = 0` are skipped from export.

After a successful export, the UI shows a completion modal and resets the selected export checkboxes without requiring a page reload. On export failure, the selection is kept so the user can retry.

The previous DOM-mutation implementation that caused repeated Livewire refreshes was removed. The current selector installation is tied to stable Livewire render/morph events; user-reported UI acceptance confirms the refresh loop is resolved.

## GDT Detail Recovery

GDT detail synchronization is resumable and missing-detail recovery applies to both purchase and sold invoices.

When headers are already complete but canonical detail is still missing, recovery remains local to the existing invoice set rather than reloading the full monthly GDT list. Transient connection/rate-limit failures use retry/backoff behavior, and unresolved missing detail can be scheduled for automatic background recovery.

Automatic recovery is bounded by configured rounds/backoff, stops when detail coverage is complete, and stops safely when the GDT token expires or the configured retry limit is reached. Queue workers should be restarted after deploying code changes that modify this recovery flow.

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

The latest focused scope included Source Data workspace contracts and Invoice source-detail export contracts. UI acceptance specifically covers the fixed export selection layout, successful Excel download flow, completion modal, checkbox reset after export, and resolution of the repeated-refresh regression.

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
- require schema changes when a new expense category is added to master data;
- use export-selection state to alter supplier-wide classification state.

## Final Closeout Gate

Before merge:

1. preserve the recorded focused test PASS and UI PASS evidence;
2. run only directly impacted Invoices/Inventory checks if further code changes are made;
3. compare the branch against current `main` and resolve base drift if needed;
4. review the final PR against `main`;
5. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
