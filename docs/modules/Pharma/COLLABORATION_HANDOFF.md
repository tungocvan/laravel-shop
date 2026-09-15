# Pharma Collaboration Handoff

## Current checkpoint — Canonical Medicine Catalog + Price List v2

- Module: `Pharma`
- Objective: **Canonical Medicine Master / SKU / Package + database-backed Price List v2**
- Implementation branch: `feat/pharma-canonical-medicine-catalog`
- Status: **IMPLEMENTATION COMPLETE / UI PASS — ready for final branch sync and merge preparation**
- Date: 2026-09-15
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`
- Consolidation: keep the current implementation branch and merge the completed phases together.

## Canonical Medicine Master

The Pharma product identity is now explicitly modeled as:

`Medicine -> MedicineVariant / SKU -> MedicinePackage`.

Price List and future downstream consumers must use exact variant/SKU/package identity rather than medicine name alone. Canonical identity, aliasing and normalization remain deterministic; ambiguous records must not be silently merged.

Medicine Master is the sole product source for Price List v2. The legacy `storage/app/excel/BANG_GIA_TONG_HOP.xlsx` is not a Price List runtime dependency.

## Price List v2 persistence and commercial contract

Price List v2 is database-backed through:

- `pharma_price_lists`;
- `pharma_price_list_items`;
- reusable `pharma_price_list_purposes`.

List types are `global` and `customer`; statuses are `draft`, `active`, `inactive`, `archived`.

Customer lists reference an active Partner classified as customer and can retain:

- `manager_user_id` — responsible user;
- `purpose_id` — reusable business purpose;
- `source_price_list_id` — Global source traceability.

Each price-list item uses deterministic variant/package identity and snapshots declared price from Medicine Master. Commercial fields are:

- `declared_price_snapshot` — readonly snapshot;
- `company_sale_price`;
- `actual_receivable_price`;
- `invoice_price`.

Invariant: prices are non-negative and `company_sale_price <= declared_price_snapshot`.

## Customer initialization and Edit invariant

An ACTIVE Global price list may initialize a Customer Draft. Initial creation may seed all source SKUs so the operator can remove non-applicable products and adjust exceptions.

After the Customer Draft is saved, its persisted `pharma_price_list_items` become the authoritative current SKU selection. The Global list is origin/reference only.

Critical regression rule:

`Global A,B,C,D,E -> Customer initialized A,B,C,D,E -> user excludes B,D -> save A,C,E -> Edit must keep A,C,E; B,D must not become checked again.`

`source_price_list_id` persists the original Global source for traceability. The Edit workspace restores that source but does not reinitialize selection from it. Invoking the source action while editing keeps the saved Draft SKU set instead of resetting to all source items. Changing the persisted source during Edit is guarded to prevent accidental reseeding.

A future explicit "refresh/update from Global" feature may propose newly available SKUs, but it must not silently restore SKUs the user previously excluded.

## Price List workspace and UI

Canonical workspace: `/admin/pharma/price-lists`.

Create/Edit uses four steps:

`Thông tin -> Chọn thuốc -> Thiết lập giá -> Kiểm tra & lưu`.

Implemented UX includes Partner customer selection, responsible user, reusable business purpose, ACTIVE Global seed source, Medicine Master filters, bounded pagination `10/25/50/100`, selection persistence, bulk receivable discount, formatted VND values, explicit SKU inclusion checkboxes, Draft save-success modal and professional quotation review/show layout.

Commercial UX rule confirmed during implementation: company sale defaults from declared price; percentage discount is applied to actual receivable price, not company sale price.

Quotation review supports selected-item Excel export; with no checkbox selection, all items in the price list are exported.

UI verification for the completed Price List workflow: **PASS**.

## Lifecycle and resolver

Draft save and activation are separate operations. Activation validates item/customer/date/overlap invariants. ACTIVE identity is not edited directly; clone/version workflow creates a new editable Draft.

`PriceResolver` / `DatabasePriceResolver` is the downstream pricing boundary:

1. applicable active Customer price;
2. applicable active Global price;
3. otherwise `NO_PRICE`.

Medicine declared price is never a sale-price fallback. The readonly `ResolvedPrice` DTO snapshots list/item/source/customer/medicine/variant/package IDs, commercial prices, currency/effective dates and resolution time.

Future Sales/Inventory/Invoice integration must consume this resolver contract; direct integration is outside this completed objective.

## Acceptance state

Latest operator-confirmed targeted gate after the source-selection regression fix:

- `PriceListV2ContractTest` + `PriceListSourceSelectionContractTest`: **14 tests / 111 assertions PASS**;
- focused Pint on Workspace/PriceList/source migration/source-selection contract: **PASS after one automatic `class_attributes_separation` formatting fix**;
- subsequent `git status -sb`: branch synchronized with origin and working tree clean;
- Price List UI: **PASS**.

Earlier canonical Medicine identity gate during this branch was also confirmed PASS before Price List closeout.

The contract tests are targeted implementation guards and do not represent a full application regression suite. Per project workflow, do not run unrelated full regression unless a directly impacted module requires it.

## Migration notes

Customer ownership/purpose uses the canonical migration:

`2026_09_15_160000_add_customer_ownership_and_purpose_to_price_lists.php`.

The accidental duplicate customer-context migration was removed and must not be restored from an old stash.

Global source traceability uses:

`2026_09_15_170000_add_source_price_list_to_price_lists.php`.

## Local stash warning

The implementation machine retains historical stashes created during multi-step synchronization. Some contain older versions of `Create.php`, `PriceList.php`, `PriceListController.php` and contract-test assertions. Do not bulk `stash pop` them onto the completed branch. Inspect any needed stash diff selectively; otherwise leave them untouched until after merge/cleanup.

## Canonical ownership boundaries retained

`Partner` remains the canonical organization/customer master. Pharma Price List references Partner; it does not duplicate customer master data.

`Muasamcong` remains owner of procurement acquisition/recovery. Drug Award procurement facts remain separate from Medicine Master and commercial Price List pricing.

Official Facility import/BHXH source-mirror ownership and safety boundaries from the previous completed Pharma objective remain unchanged.

## Deferred scope

- automatic downstream Sales/Inventory/Invoice integration beyond `PriceResolver`;
- silent/automatic Global source refresh of Customer Draft selections;
- fuzzy/AI medicine identity merge;
- unrelated Inventory/Invoices/Partner refactors;
- unrelated Official Facility/BHXH changes;
- Pharma runtime enablement changes.

## Previous completed checkpoints

Official Facility Import + BHXH Source Mirror was merged to `main` via PR #166 on 2026-09-06. MaSoThue lookup CLI was merged via PR #168. Drug Award Allocation & Hospital Contract Management was merged earlier via PR #165. Those objectives remain complete and their ownership/safety contracts are preserved by this Price List work.
