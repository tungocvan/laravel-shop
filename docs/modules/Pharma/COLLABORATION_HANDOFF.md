# Pharma Collaboration Handoff

## Current checkpoint — Drug Bid Intelligence + Price List v2

- Module: `Pharma`
- Objective: **Canonical Medicine Master / SKU / Package + Drug Bid Intelligence + database-backed Price List v2**
- Implementation branch: `feat/pharma-price-list-bid-ui-professional`
- Parent implementation branch: `feat/pharma-drug-bid-intelligence`
- Status: **BATCH C IMPLEMENTATION COMPLETE — LOCAL ACCEPTANCE CHECKPOINT PENDING**
- Date: 2026-09-15
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`
- Consolidation: keep the implementation phases together and use one final pull/test/merge cycle where practical.

## Canonical Medicine Master

The Pharma product identity is explicitly modeled as:

`Medicine -> MedicineVariant / SKU -> MedicinePackage`.

Price List and downstream consumers must use exact variant/SKU/package identity rather than medicine name alone. Canonical identity, aliasing and normalization remain deterministic; ambiguous records must not be silently merged.

Medicine Master is the sole product source for Price List v2. The legacy `storage/app/excel/BANG_GIA_TONG_HOP.xlsx` is not a Price List runtime dependency.

## Drug Bid Intelligence architecture

Procurement ownership remains separated from Pharma commercial pricing:

`Muasamcong -> Drug Award Projection -> DrugBidAward -> canonical bid match -> BidPriceIntelligenceService -> Medicine / Price List`.

`Muasamcong` owns procurement acquisition/recovery and source facts. `Pharma` owns projection, canonical medicine resolution, bid matching, bid intelligence and immutable Price List evidence.

A bid award may resolve to Medicine, Variant or Package. The matcher must stop at the deepest deterministic level and must never guess an ambiguous Variant/Package. Manual confirmed canonical matches take precedence over automatic rematching. If identity-critical source fields change, a manual match is preserved and marked for review instead of being silently overwritten.

The Price List resolver invariant remains unchanged:

1. applicable ACTIVE Customer price;
2. applicable ACTIVE Global price;
3. otherwise `NO_PRICE` / null.

Bid prices are reference evidence only. They must never become a `DatabasePriceResolver` fallback and must never silently mutate `company_sale_price`, `actual_receivable_price` or `invoice_price`.

## Latest bid award default

For each selected Medicine Variant/Package, Price List automatically proposes the most recent correctly matched bid award. Ordering is:

1. `decision_date DESC`;
2. fallback/tie `published_at DESC`;
3. `id DESC`.

The primary displayed award date is `decision_date`, falling back to `published_at` when needed.

The compact Price List evidence contains only the operational fields requested for pricing work:

- winning quantity;
- winning unit price;
- decision number;
- award date;
- winning contractor;
- source (`Mua sắm công` or `Nhập thủ công`).

The default award is automatic. The user does not have to choose an award every time. `Xem lịch sử` / `Chọn kết quả khác` may select another historical award as reference without changing commercial prices.

## Manual award fallback

When no matched award exists, Price List must present `Chưa có kết quả trúng thầu` and an explicit `Bổ sung` / `Cập nhật thủ công` action.

Manual award data is a reusable Pharma `DrugBidAward` with `source_type = manual`; it is not private data embedded only inside one Price List. Required operational inputs are winning quantity, winning unit price, award date and winning contractor; decision number and note are optional where the underlying model permits it. The record must retain creator/audit metadata where supported.

A later Muasamcong synchronization must not silently delete or overwrite a manual award. A likely equivalent procurement record is a review/reconciliation case.

## Price List bid evidence

Price List uses live bid intelligence for discovery and snapshots the selected reference when the item is saved. `PriceListItemBidEvidence` is immutable historical evidence for the commercial decision and survives later source changes according to its persistence contract.

Editing a Draft preserves the already-captured evidence snapshot even when a newer matching award now exists. A different historical award becomes the saved evidence only after an explicit user selection. Saving a Price List item never copies the winning bid price into commercial price fields.

## Price List v2 persistence and commercial contract

Price List v2 is database-backed through:

- `pharma_price_lists`;
- `pharma_price_list_items`;
- reusable `pharma_price_list_purposes`;
- bid-evidence persistence for the selected award reference.

List types are `global` and `customer`; statuses are `draft`, `active`, `inactive`, `archived`.

Customer lists reference an active Partner classified as customer and can retain `manager_user_id`, `purpose_id` and `source_price_list_id`.

Each price-list item uses deterministic variant/package identity and snapshots declared price from Medicine Master. Commercial fields are:

- `declared_price_snapshot` — readonly snapshot;
- `company_sale_price`;
- `actual_receivable_price`;
- `invoice_price`.

Invariant: prices are non-negative and `company_sale_price <= declared_price_snapshot`.

Commercial UX rule: company sale defaults from declared price; percentage discount is applied to actual receivable price, not company sale price.

## Customer initialization and Edit invariant

An ACTIVE Global price list may initialize a Customer Draft. Initial creation may seed all source SKUs so the operator can remove non-applicable products and adjust exceptions.

After the Customer Draft is saved, its persisted `pharma_price_list_items` become the authoritative current SKU selection. The Global list is origin/reference only.

Critical regression rule:

`Global A,B,C,D,E -> Customer initialized A,B,C,D,E -> user excludes B,D -> save A,C,E -> Edit must keep A,C,E; B,D must not become checked again.`

`source_price_list_id` persists the original Global source for traceability. Edit must not silently reinitialize excluded SKUs.

## Professional Price List UI checkpoint

Create/Edit uses four steps:

`Thông tin -> Chọn thuốc -> Thiết lập giá -> Kiểm tra & lưu`.

The UI remains full-width on Create/Edit. Bid intelligence is integrated into the Medicine Master / pricing workflow rather than rendered as a long standalone card above the table.

Required interaction:

- `Đã có -> Xem`: show the latest selected award and history/alternative selection;
- `Chưa có -> Bổ sung`: enter a reusable manual Pharma award;
- bid detail remains compact and secondary to the commercial pricing inputs;
- Desktop/Tablet/Mobile must remain usable and conform to `ADMIN_UI_STANDARD`;
- saving a Draft shows the success modal and returns to `/admin/pharma/price-lists/` through the existing return action.

Partner remains the canonical customer master; the Customer field links to `/admin/partners` rather than duplicating organization data.

The Price List detail table is intentionally compact: product/SKU, medicine information, package, medicine group, declared price, company sale price and bid result; receivable/invoice prices are not duplicated in this detail table.

## Excel Designer v2 checkpoint

Price List detail export now uses persistent per-admin export profiles rather than browser-only configuration. Profiles store branding/content, selected columns, global column order, custom group placement, custom headers, alignment, width, datatype/decimals and page setup.

The Designer exposes the full Pharma master/export catalog and supports cross-group drag/drop. `column_groups` changes only Designer organization; Excel output order remains controlled by `column_order`. Existing profiles without custom group placement fall back to canonical column groups.

Logo/signature support includes browser preview and actual PhpSpreadsheet drawings. Removal/replacement is transactional from the operator's perspective: opening the Designer and removing an image does not delete the persisted file until the profile is successfully saved; cancelling restores persisted state.

Export semantics remain: when detail-table checkboxes contain item IDs, export only those items; when none are selected, export every item in the Price List. Saved page size/orientation/margins/centering/scaling are applied to the workbook. Configured string/number/date types are applied per cell; a value configured as date but not parseable falls back to an explicit string rather than aborting the entire export.

Profile JSON import/export is portable and additive/backward-compatible with profiles that predate custom `column_groups`.

## Performance and backfill

Bid intelligence uses batch lookup (`forItems`) for selected Variant/Package identities to avoid N+1 queries.

Historical matching/backfill is a separate resumable command, not a migration. Expected operational options include dry-run, chunking, ID ranges, unmatched-only and auto-rematch modes. Manual confirmed matches must not be rematched by default.

## Batch C acceptance checkpoint

Local acceptance is now required before Batch C can be called PASS. Run the focused Pharma tests for professional Price List UI, Excel export/profile v2, bid evidence immutability and bid intelligence. Run Pint only against the touched Batch C PHP files. Apply outstanding Pharma migrations before browser testing.

Manual acceptance must verify:

- create/edit Price List Desktop, Tablet and Mobile;
- customer initialization from an ACTIVE Global list;
- customer Partner selection/link;
- latest bid reference and explicit historical selection;
- Draft edit keeps immutable evidence instead of silently moving to a newer award;
- manual bid fallback;
- detail-table selected-only export and no-selection export-all;
- Excel Designer cross-group drag/drop persists after save/reopen;
- Inspector custom header/type/alignment/width/decimals persists;
- logo/signature preview, cancel safety and saved workbook drawings;
- page setup in the generated workbook;
- real generated `.xlsx` opens cleanly in Excel/LibreOffice.

Frontend note: the operator previously reported `npm run build` failing because Vite/Rollup could not resolve an import. Do not report frontend build PASS until that existing failure is reproduced/fixed or shown to be unrelated.

Final UI acceptance must be checked on Desktop, Tablet and Mobile before declaring `UI PASS`.

## Local stash warning

Historical stashes exist from earlier multi-step synchronization. Some contain older Price List/Invoices changes. Do not bulk `stash pop` them onto this implementation branch. Inspect selectively only when explicitly needed; leave unrelated Invoices stashes untouched.

## Deferred scope

- automatic downstream Sales/Inventory/Invoice integration beyond the existing Pharma service/resolver contracts;
- silent/automatic Global source refresh of Customer Draft selections;
- fuzzy/AI medicine identity auto-confirmation;
- advanced min/max/average/median bid analytics in the central Price List create UI;
- unrelated Inventory/Invoices/Partner refactors;
- unrelated Official Facility/BHXH changes;
- Pharma runtime enablement changes.

## Previous completed checkpoints

Official Facility Import + BHXH Source Mirror was merged to `main` via PR #166 on 2026-09-06. MaSoThue lookup CLI was merged via PR #168. Drug Award Allocation & Hospital Contract Management was merged earlier via PR #165. Those objectives remain complete and their ownership/safety contracts are preserved by this work.
