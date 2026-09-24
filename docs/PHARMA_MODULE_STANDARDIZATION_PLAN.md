# Pharma Module Standardization Plan

## Purpose

Standardize `Modules/Pharma` as one operational chain while preserving domain ownership and the repository Admin UI standard.

Canonical business flow:

`Medicine Master -> HSSP -> Supplier -> Drug Bid Award -> Allocation -> Commercial Policy / User Assignment -> Price List -> Inventory -> Bid Sale Issue -> Commission`

The Dashboard is the operations hub. It summarizes state, highlights actionable exceptions and routes users to the owning workspace. It must not become a second CRUD surface.

## Domain map

| Domain | Canonical workspace | Ownership |
| --- | --- | --- |
| Medicine Master | `/admin/pharma/medicines` | Canonical medicine identity |
| HSSP | `/admin/pharma/hssp` | Medicine dossier/version/validity |
| Official facilities | `/admin/pharma/official-facilities/*` | Official facility source/import |
| Supplier commercial | `/admin/pharma/supplier-trackings` | Supplier/source commercial tracking |
| Drug bid awards | `/admin/pharma/drug-bid-awards` | Tender award evidence and canonical link |
| Allocation | award allocation workspace | Hospital allocation |
| Commercial policy | award commercial-policy workspace | Product commission policy and management assignment |
| Price lists | `/admin/pharma/price-lists` | Global/customer price documents |
| Inventory | `/admin/pharma/inventory` | Batch/expiry/warehouse stock ledger |
| Receipts | `/admin/pharma/inventory/receipts` | Stock-in documents |
| Issues | `/admin/pharma/inventory/issues` | Stock-out documents |
| Bid sales | issue bid-sale workflow | Tender sale, actual batch posting, deferred supply |
| Commission | `/admin/pharma/inventory/commissions` | Posted bid-sale commission snapshot/reporting |

## Dashboard contract

Dashboard answers three questions:

1. What is the current operational state?
2. What requires attention?
3. Which owning workspace should the operator open?

Dashboard sections:

- Operational KPIs.
- Action-required queue.
- Inventory and supply.
- Bid and commercial.
- Management workspace directory.

Dashboard queries belong in `PharmaDashboardService`, not Blade. Each domain summary is failure-isolated so one unavailable table/section does not take down the whole dashboard.

## Standardization checklist for every Pharma workspace

- Canonical `Admin::layouts.master` shell and intentional content width.
- Clear title, context and primary action.
- Back/navigation points to the owning Pharma workspace or Dashboard.
- Visible, consistent form borders/focus/read-only states.
- Search/filter controls remain compact and include reset when multiple filters are active.
- Bounded pagination for production datasets.
- Stable-ID checkbox selection; header checkbox means visible page.
- Import/export uses canonical infrastructure where applicable.
- Empty state explains the next useful action.
- Permission-aware actions in both UI and server route/action.
- No database queries in Blade.
- Responsive table overflow and mobile-safe actions.
- Consistent Vietnamese business terminology.
- Focused contract tests for runtime view/service/route behavior.

## Delivery phases

### Phase 1 — Operations Dashboard

- Expand `PharmaDashboardService` across master data, inventory, commercial, sales and attention domains.
- Rebuild Dashboard as an operations hub.
- Add permission-aware navigation and focused tests.

### Phase 2 — Navigation and shared UI consistency

Audit page headers, Dashboard-back behavior, primary/secondary actions, filter/reset patterns, pagination and empty states. Extract shared Pharma UI fragments only where repetition is proven.

### Phase 3 — Master Data

Audit Medicine, HSSP and Official Facilities. Verify canonical identity ownership, import/export UX, conflict/review flows and permission boundaries.

### Phase 4 — Commercial and Bid

Audit Supplier Tracking, Drug Bid Award, Allocation, Commercial Policy/User Assignment and Price Lists. Verify navigation from award -> allocation -> policy -> pricing without duplicated master data.

### Phase 5 — Inventory and Finance

Audit Inventory, Receipts, Issues, Bid Sales, Deferred Supply and Commission. Verify posting/revert lifecycle, immutable dates/snapshots, filters, selected export and audit-safe behavior.

### Phase 6 — Regression closeout

Run focused tests during each phase. Run the full `Modules/Pharma` regression only after feature work is merged to `main`, followed by representative desktop/mobile UI acceptance.
