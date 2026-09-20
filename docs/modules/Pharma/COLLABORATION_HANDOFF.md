## Checkpoint — Price List pagination selection identity

- Branch: `fix/pharma-price-list-pagination-selection`.
- Base: `main` at `cf06ebca1381f7a1679f6c261479cb2255d7bd17`.
- Route: `/admin/pharma/price-lists/create`, step 2 (`Chọn thuốc`).
- Root cause: product rows did not have a stable Livewire DOM key. When pagination replaced the dataset, Livewire could reuse checkbox nodes by row position and visually carry checked state onto unrelated products on the next page while the server-side `selectedRows` count remained correct.
- Fix: each catalog row now uses the canonical variant/package row key as `wire:key`; each checkbox also has a matching stable HTML id. Selection remains server-owned and persists only for the exact selected SKU/package keys.
- Selection semantics remain unchanged: the header checkbox is page-scoped, while `Chọn tất cả kết quả` remains an explicit separate action.
- No schema migration and no Price List business-rule change.
- Static diff/whitespace validation: PASS (`git diff --check`).
- Automated tests were not run in the implementation workspace because PHP is unavailable there; operator focused/module tests and manual UI acceptance remain pending at this checkpoint.
- Operator Test 1 (`PriceListV2ContractTest`): PASS (inferred from progression to Test 2 under the agreed stop-on-failure gate).
- Initial Price List regression: 57 passed / 2 failed (596 assertions). Both failures were stale assertions in `PharmaPriceListPipelineTest`: one still required the removed legacy-workbook `analysisSummary()` helper; the other prohibited the accepted explicit `Chọn tất cả kết quả` action. The contract now asserts the current database-backed paginator and the distinct page/all-result selection controls, including stable catalog row identity. Re-run pending.
- Operator regression after aligning stale contracts: 59 passed (598 assertions).
- First manual UI re-check still reproduced positional checkbox carry-over. Row-level keys alone were insufficient for the checkbox DOM property, so the follow-up fix keys the complete catalog page body by current page + rendered row identities, keys each checkbox directly, and renders its checked state explicitly from `selectedRows`. Follow-up automated and UI acceptance pending.

---

## Final acceptance — Official Facilities source regions / BHXH / XLSX round-trip

- Operator acceptance: focused tests PASS and UI PASS, including the exported workbook import flow.
- Final Pharma regression after aligning the BHXH source-first contract: **176 passed / 20 failed (1374 assertions)**.
- Compared with the immediately preceding regression (175 passed / 21 failed), the only Official Facilities failure was removed. Remaining 20 failures are documented pre-existing/out-of-scope Pharma baseline groups (Medicine UI/contracts, dashboard contracts, Drug Award SQLite test schema/import-export, Price List contracts).
- Branch review against `main`: branch is 37 commits ahead and 0 behind at closeout review; changes are scoped to Official Facilities/BHXH/source export-import plus this handoff, with the existing dashboard link adjustment.
- Official Facilities regression attributable to this branch at closeout: **0**.
- No schema migration was added by the final XLSX/source-first fixes.
- Ready for PR and merge to `main`.

---

## Regression gate — BHXH source-first contract alignment

- Full Pharma regression reported by operator before this fix: 175 passed / 21 failed (1367 assertions).
- Exactly one reported failure was in the current Official Facilities scope: `Modules/Pharma/Tests/Unit/BhxhFacilityLookupContractTest` still asserted the superseded literal `Không OCR / không bypass CAPTCHA`.
- Updated that contract to assert the new source-first behavior: cached source data can be viewed without CAPTCHA, while a fresh BHXH request still requires a human-entered CAPTCHA before the lookup request is submitted.
- No production behavior changed in this commit; test contract only.
- Remaining 20 reported failures are the pre-existing/out-of-scope Pharma baseline (Medicine/Drug Award/dashboard/Price List/test-schema groups) and must remain documented rather than being mixed into this Official Facilities refactor.
- Awaiting pull + focused BHXH/Official Facilities tests, then final Pharma regression. Expected regression gate if no new issue appears: 176 passed / 20 failed.

---

## Fix — Official Source workbook helper sheets

- Reproduced import incompatibility from the operator-provided workbook: the primary `Worksheet` contains the exported facility rows, while helper sheets such as `Nguon` and `Ma_dia_ban` do not carry the round-trip facility headings.
- `OfficialSourceFacilitiesImport` now implements `WithMultipleSheets` and `SkipsUnknownSheets`, explicitly importing only `Worksheet` and ignoring helper/reference sheets.
- Existing row validation and `(source, external_id)` identity rules remain unchanged.
- No schema migration.
- Added focused contract coverage for sheet scoping. Awaiting pull + focused test + re-import of the operator workbook.

---

## Checkpoint — Official Source smart XLSX export/import

- Source repository Export is always enabled.
- Export precedence: checked rows -> current filter result -> entire source repository when neither selection nor filters constrain the query.
- Current filter values are posted with the export form, so export is not limited to the current pagination page.
- Workbook remains native XLSX and now includes stable round-trip columns `Source`, `External ID`, `Source Province Code`, and `Source District Code` in addition to operator-facing columns.
- Added Import Excel modal for users with `import_pharma_official_facilities` permission; accepts xlsx/xls generated by this screen.
- Import identity is `(source, external_id)`; creates missing records and updates changed records in the Pharma source mirror only. It never writes Partner.
- Import reports created/updated/unchanged/error row counts and rejects workbooks missing required round-trip columns.
- No schema migration.
- Awaiting pull + focused contract test + export-all/filter/selection and import round-trip UI smoke.

---

## Checkpoint — BHXH source-first lookup UX

- Route `/admin/pharma/official-facilities/bhxh` now prefers the Pharma official source mirror before making a new BHXH lookup.
- Selecting a BHXH source partition or district reads active rows from `pharma_official_source_facilities` using source + source province code + optional source district code.
- Existing cached rows render immediately with count and last-sync timestamp; no CAPTCHA is required to view them.
- CAPTCHA is only validated client-side when the operator explicitly submits a fresh BHXH lookup. The action label becomes `Tra cứu lại BHXH` when cached data exists.
- Cached data never enables `Đồng bộ vùng này`; that action is enabled only by a successful fresh BHXH response/snapshot.
- Empty cache explains that CAPTCHA is needed for first lookup.
- No schema migration and no change to source identity or Partner boundary.
- Awaiting pull + focused contract test + UI smoke for cached whole-partition, cached district, and fresh refresh flow.

---

## UI refinement — Official Source compact filters + XLSX + numbered pagination

- Operator UI feedback: source repository layout works, but search should be shorter so reset stays on the filter row; export must be a native multi-column Excel workbook; pagination should expose page numbers.
- Search placeholder/width is compacted and `Xóa bộ lọc` occupies the final desktop grid slot when filters are active.
- Selected-record export now uses `maatwebsite/excel` and downloads a real `.xlsx` workbook with separate columns for source, CSKCB code/name, ERP business region, province/city, BHXH source partition/code, district/code, status and last sync time.
- Export remains selection-only and read-only with respect to Partner.
- Pagination remains white Admin UI and now renders current/nearby page numbers plus first/last page and previous/next controls.
- No schema migration.
- Awaiting focused contract test + UI/export smoke.

---

## Checkpoint — Official Source repository region/filter/export UX

- Route: `/admin/pharma/official-facilities/source`.
- Operator confirmed BHXH lookup UI PASS before this refinement.
- Source repository now displays ERP business region and filters by business region before province/source partition.
- Facility search continues through shared `<x-search>` and searches source ID/name/geography fields.
- Added page-scoped row checkboxes, select-all-current-page, and selected-record CSV export (maximum 500 IDs per request). Export does not write to Partner.
- Province and BHXH source-partition options are constrained by the selected ERP business region.
- Replaced framework pagination rendering with an explicit white Admin UI pagination surface to avoid dark pagination styling.
- No schema migration; business region remains derived from `BhxhProvinceCatalog`.
- Focused contract test extended in `tests/Feature/Pharma/OfficialFacilityRegionContractTest.php`.
- Previous broad Pharma regression baseline on this branch: 174 passed / 20 failed; failures are outside this Official Facilities scope and include pre-existing Medicine/PriceList/DrugAward/dashboard contracts. Focused Official Facilities test before this refinement: 2 passed / 10 assertions.
- Awaiting pull + focused test + source repository UI/export smoke.

---

## Checkpoint — Official Facilities admin integration + ERP business regions

- Branch: `refactor/pharma-official-facilities-regions`
- Base: `main` at `42510073c527ef46398d3143020116560a921d7f`.
- Pharma Dashboard `Cơ sở KCB chính thức` now enters the Pharma-owned source repository instead of dropping operators directly into file import.
- BHXH lookup adds an ERP business-region selector before province/city: Miền Bắc, Bắc Trung Bộ, Nam Trung Bộ, Tây Nguyên, Miền Đông / Đông Nam Bộ, Miền Tây / Tây Nam Bộ.
- Business regions are operator/reporting groupings only. BHXH source partition codes remain durable source identities and are not rewritten.
- Legacy BHXH geography labels remain classified so historical source partitions do not disappear from lookup.
- Successful sync messaging explicitly states the destination is the Pharma official source repository and that records are not written directly to Partner.
- Persistence boundary remains unchanged: `pharma_official_source_sync_batches` tracks sync runs and `pharma_official_source_facilities` stores source facilities; Partner remains a later reviewed/imported canonical boundary.
- Added focused contract coverage: `tests/Feature/Pharma/OfficialFacilityRegionContractTest.php`.
- No schema migration.
- Awaiting operator targeted test + Pharma impacted regression + desktop/mobile UI smoke.

---

## UI refinement — Medicine Catalog filters and columns

- Filter controls are rebalanced into one desktop row. `Xóa bộ lọc` occupies the final slot only when a select/per-page value differs from its default; free-text search remains independent.
- Reset restores HSSP, master quality, circular group, special-control and per-page selectors to defaults without clearing the search field.
- `Variant / SKU` is removed from desktop and mobile catalog presentation; SKU remains searchable and remains part of the underlying Medicine/Variant model.
- `Quy cách` receives additional table width so packaging text is readable after adding `Nhóm thuốc`.
- Existing HSSP delete protection and centered delete confirmation/result modals are retained; the mobile delete action now follows the same HSSP-disabled behavior.
- No schema/data migration.

---

## Checkpoint — Medicine Catalog group + guarded delete UX

- Branch: `fix/pharma-medicine-catalog-delete-ux`
- Based on the HSSP validity mapping branch so the previous approved Medicine/HSSP work is retained.
- Desktop catalog adds `Nhóm thuốc` from `circular_group`; mobile detail also exposes the same field.
- Medicine rows with any HSSP profile keep the Delete action visible but disabled/muted. Backend `MedicineService::delete()` remains the authoritative guard.
- Eligible deletes use an explicit centered confirmation modal; success and failure are surfaced in a centered result modal instead of relying only on flash banners.
- Medicine edit/create form adds a visible `← Quay về Danh mục thuốc chuẩn` action.
- No migration/schema change.

---

## Checkpoint — Medicine Master validity derived from HSSP

- Branch: `fix/pharma-medicine-hssp-validity-sync`
- Medicine edit now treats the current HSSP dossier as the preferred source for the two management validity fields when HSSP values exist.
- Mapping: HSSP `registration.metadata.effective_to` → Medicine Master `visa_validity_date` (Hiệu lực Visa).
- Mapping: HSSP `gmp.metadata.effective_to` → Medicine Master `gmp_certification_date` (GMP cơ sở sản xuất).
- Backward compatibility: if no HSSP dossier/value exists, the existing Medicine values remain the fallback.
- The generic Dossier engine remains unaware of Pharma semantics; mapping is isolated in `HsspMedicineValidityService`.
- No migration/schema change.

---

## HSSP Module Snapshot v2 checkpoint — 2026-09-19
- Fresh Pharma Snapshot v2 runtime capture verified HSSP graph: dossier=1, template=1, template_items=5, items=5, attachments=4. GMP/GPLH metadata and Google Drive attachment metadata are present in the ZIP.

- Pharma HSSP related snapshot graph now points at the actual `pharma_medicine_profiles` owner table.
- Invalid related graph declarations now fail backup instead of silently producing an apparently successful snapshot without Dossier/HSSP data.
- Fresh end-to-end Pharma snapshot verification remains required before PR/merge.

# Pharma Collaboration Handoff

## Current checkpoint — HSSP lifecycle delete + runtime Pharma queue

- Module: `Pharma` with reusable dossier core under `App\\Dossiers`
- Branch: `refactor/pharma-hssp-dossier-engine`
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS + QUEUE/DRIVE LIFECYCLE PASS — READY FOR PR/MERGE**
- Date: 2026-09-19

### Current implementation

- Runtime module registry publishes queue metadata only after ModuleStateResolver resolves actual enablement; `run-queue.sh` consumes the resolved registry. Operator verified Pharma is runtime-enabled and PM2 runs `default,admission-documents,pharma` with timeout 600 / tries 3.
- Generic dossier upload job now lives under `App\\Dossiers\\Jobs`; the reusable storage engine no longer imports a Pharma upload job. Pharma selects the named `pharma` queue at its adapter boundary.
- Dossier attachments persist Google Drive `remote_id` for deterministic lifecycle cleanup; legacy attachments without it fall back to scoped remote-path lookup.
- HSSP delete is explicit and queued. It removes managed Google Drive files, managed Local files, dossier/items/attachments and the MedicineProfile, while preserving the Medicine Master record.
- Cleanup is retry-safe: Drive 404/missing legacy paths are treated idempotently; failed cleanup leaves dossier/profile records available for retry instead of silently losing cleanup evidence.
- HSSP index includes a destructive confirmation modal explaining Local/Drive cleanup and Medicine preservation.

### Final acceptance — 2026-09-19

Operator verification:

```text
Pharma tests: 133 passed (1035 assertions)
Pint: PASS after accepted formatting sync
UI: PASS
DeleteHsspDossier: DONE on queue pharma
UploadDossierAttachmentToGoogleDrive: multiple uploads DONE on queue pharma
```

The Justone HSSP lifecycle was exercised end-to-end: delete through the confirmation UI, queued Local/Google Drive cleanup while preserving Medicine Master, recreate the product dossier, and queued Google Drive uploads through the generic dossier job. Runtime ModuleRegistry/PM2 discovery was also verified with Pharma enabled from runtime state and the `pharma` queue active.

---

# Pharma Collaboration Handoff

## Current checkpoint — HSSP reusable dossier engine implementation

- Module: `Pharma` with reusable core under `App\\Dossiers`
- Branch: `refactor/pharma-hssp-dossier-engine`
- Status: **IMPLEMENTED — AWAITING LOCAL MIGRATION / TARGETED TEST / UI ACCEPTANCE**
- Date: 2026-09-19
- Parent checkpoint: Medicine Master/GPLH refactor merged to `main` via PR #204.

### Implemented scope

- Generic dossier templates, template items, dossier instances, items and attachments.
- Reusable Blade component `<x-dossier.editor>` driven by template metadata schema.
- Pharma default HSSP template: GMP, GPLH decision, HDSD, label and product-change sections.
- GMP expiry and registration/GPLH expiry are required; per-item uploads remain optional.
- Operators can append custom dossier items with optional validity date and attachments.
- Optional combined/master files are supported.
- Attachments are local-first and mirror through the existing System Google Drive OAuth connection under the configured `Laravel-Backup` root.
- Attachment records retain checksum, local path, remote path and sync status.
- Existing HSSP MedicineProfile remains the Pharma owner; dossier engine does not create a second Medicine identity.
- HSSP create/edit now composes the reusable dossier editor.

### Acceptance pending

Run the new migration, focused HSSP dossier contract test, impacted System Google Drive upload contract test, then Pharma module regression appropriate to the changed HSSP boundary. Perform desktop/mobile UI smoke for create/edit, required validity validation, optional item upload, custom item, master upload and Drive sync status.

---

# Pharma Collaboration Handoff

## Current checkpoint — Medicine Master / GPLH integrity accepted

- Module: `Pharma`
- Implementation branch: `refactor/pharma-medicine-master-gplh-ui`
- Status: **IMPLEMENTATION COMPLETE — TARGETED TEST PASS + UI PASS — READY FOR PR/MERGE**
- Date: 2026-09-19
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`

## Accepted scope

Medicine Master now keeps GPLH/registration identity separate from the generated medicine code/SKU, preserves registration display values during edit, searches medicine aliases through the real alias schema, persists the complete owner Excel catalog metadata on import update, and guards canonical identity collisions with actionable feedback.

Price List v2 now supports guarded checkbox bulk deletion for removable DRAFT/INACTIVE lists.

Drug Bid Award review now supports guarded bulk unlink. Both single and bulk unlink clear the canonical match and the direct `pharma_drug_bid_awards.medicine_id / medicine_code` reference transactionally, returning `medicine_match_status` to unresolved. This prevents stale direct bid references from incorrectly blocking Medicine deletion.

Operator acceptance on 2026-09-19:

```text
Targeted tests: PASS
UI: PASS
Pint formatting synchronized on accepted files
```

Historical local stashes may remain on operator machines. Do not bulk-pop or bulk-drop them; inspect individually only if explicitly needed.

## Next authorized work after merge

Create a fresh branch for the approved HSSP refactor. Target architecture: reusable Document/Dossier Set engine with configurable table-of-contents templates, dynamic metadata fields, optional per-item attachments, optional combined/master file, version/history, and local ↔ Google Drive storage synchronization. Pharma owns the HSSP adapter and requires GMP validity plus registration/GPLH validity metadata before saving/confirming the product dossier. The reusable engine must not depend on Pharma and should be suitable for other modules.

---

# Pharma Collaboration Handoff

## Current checkpoint — Price List v2 + Excel Designer v3.2 accepted

- Module: `Pharma`
- Implementation branch: `feat/pharma-price-list-bid-ui-professional`
- Parent implementation branch: `feat/pharma-drug-bid-intelligence`
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS + PINT PASS — READY FOR PR/MERGE**
- Date: 2026-09-16
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`
- Reusable Excel standard: `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`

## Scope accepted

This branch completes the professional database-backed Pharma Price List workflow and the reusable Excel Designer reference implementation. The canonical product identity remains `Medicine -> MedicineVariant / SKU -> MedicinePackage`; Price List must use deterministic variant/package identity and must not silently merge ambiguous medicine identities.

Price List resolver precedence remains:

1. applicable ACTIVE Customer price;
2. applicable ACTIVE Global price;
3. otherwise `NO_PRICE` / null.

Bid prices are reference evidence only. They must never become a commercial-price fallback or silently mutate `company_sale_price`, `actual_receivable_price` or `invoice_price`.

## Price List v2 invariants

Price List v2 is database-backed through `pharma_price_lists`, `pharma_price_list_items`, reusable purposes and bid-evidence persistence. List types are `global` and `customer`; statuses are `draft`, `active`, `inactive`, `archived`.

Customer lists use Partner as the canonical customer master and may retain `manager_user_id`, `purpose_id` and `source_price_list_id`. Customer creation can initialize from an ACTIVE Global list.

After a Customer Draft is saved, persisted `pharma_price_list_items` are authoritative. The Global source becomes traceability/reference only. Edit must not silently re-add products the operator previously excluded.

Commercial UX accepted:

- company sale defaults from declared price;
- declared-price discount percentage applies to actual receivable price;
- Customer links to `/admin/partners`;
- save uses success feedback and returns through the Price List route;
- Price List delete is available with explicit confirmation;
- bid intelligence stays secondary to commercial pricing inputs.

## Bid evidence

`Muasamcong` owns procurement acquisition/source facts. `Pharma` owns canonical bid matching, bid intelligence and Price List evidence.

For each selected Variant/Package, the default proposal is the latest correctly matched award ordered by `decision_date DESC`, then `published_at DESC`, then `id DESC`. The operator can explicitly inspect history or choose another result.

When no matched award exists, the operator can add a reusable manual Pharma award. Manual confirmed matches must not be silently overwritten by later automatic synchronization.

Price List snapshots the selected bid reference when the item is saved. Editing a Draft preserves its captured evidence unless the operator explicitly selects another historical result. Saving never copies a winning bid price into commercial price fields.

## Excel Designer v3.2 accepted reference implementation

The Price List export uses persistent per-admin profiles and supports:

- profile create, duplicate, set-default and delete;
- server-side JSON library plus local JSON import;
- branding, selected columns and page setup sections;
- searchable/grouped data library;
- explicit Excel column order;
- isolated per-column Inspector draft state;
- custom header, width, alignment, datatype and decimals;
- Times New Roman typography and configurable table styling;
- orientation, paper size, margins, centering and scaling;
- logo/signature preview and configurable dimensions;
- signing location, full `Ngày tháng năm`, signatory title and signatory name.

Export semantics remain: when detail-table checkboxes contain item IDs, export only those items; when none are selected, export every item in the Price List.

### JSON/media contract

Profile JSON is portable configuration and must not blindly embed binary media. Logo may be recovered in the appropriate profile/user scope. Signature recovery is identity-sensitive and must match normalized `signatory_title + signatory_name`; it must never fall back to another person's signature.

An explicit signing date restored from JSON/profile must be preserved. The current date is only the default when that field is empty. Logo/signature custom dimensions remain profile-scoped.

### Runtime regression rules

The final accepted Designer establishes these mandatory rules:

- imported/duplicated profile names are collision-safe under `(user_id, name)` uniqueness;
- destructive actions capture the exact target before confirmation;
- Livewire confirmation state uses `pendingConfirmAction` and `pendingConfirmValue`;
- the execution method is `executeConfirmedAction()`;
- a Livewire public property and public action method must never share the same name;
- profile delete and JSON delete require explicit confirmation;
- static tests alone are insufficient for destructive UI interactions: browser UI acceptance is mandatory.

The reusable implementation standard is `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`. Other modules should implement that standard within their own ownership boundary. They may use Pharma v3.2 as a reference but must not introduce a hard dependency on Pharma solely to reuse the Designer.

## Final acceptance — 2026-09-16

Operator verification after final Pint formatting:

```text
Tests: 14 passed (126 assertions)
Pint: PASS — 6 files
UI: PASS
Working tree: clean and synchronized with origin before this handoff commit
```

Focused regression suite:

```text
Modules/Pharma/Tests/Unit/PriceListExportProfileRuntimeRegressionContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelJsonMediaRoundTripContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelDesignerV3ContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelMediaSizingContractTest.php
```

Final formatting commit before this handoff: `36b9ee29 style(pharma): finalize price list excel designer formatting`.

Frontend note: an earlier `npm run build` attempt failed because Vite/Rollup could not resolve an import. This closeout does **not** claim frontend build PASS; that issue is outside this targeted Pharma acceptance checkpoint.

## Local stash safety

Historical stashes remain on the operator machine from earlier synchronization checkpoints. Do not bulk-pop or bulk-drop them during this merge. The closeout working tree was clean without restoring those historical patches. Inspect any stash individually only when explicitly needed; unrelated Invoices stashes remain untouched.

## Deferred scope

- automatic downstream Sales/Inventory/Invoice integration beyond existing Pharma contracts;
- silent/automatic Global-source refresh of persisted Customer Draft selections;
- fuzzy/AI medicine identity auto-confirmation;
- advanced bid analytics in the central Price List create UI;
- unrelated Inventory/Invoices/Partner refactors;
- unrelated frontend/Vite import-resolution repair;
- unrelated Pharma runtime enablement changes.

## Previous completed checkpoints

Official Facility Import + BHXH Source Mirror was merged to `main` via PR #166 on 2026-09-06. MaSoThue lookup CLI was merged via PR #168. Drug Award Allocation & Hospital Contract Management was merged earlier via PR #165. Those ownership and safety contracts remain preserved.

### Docker production hardening — 2026-09-19

Production Docker was aligned with the accepted HSSP runtime: PHP accepts 50 MB files / 64 MB POST bodies, Nginx accepts 100 MB request bodies, and the general Docker queue consumes `pharma` by default. `app` and `queue` already share the `app_storage` volume, so staged dossier files remain visible to the asynchronous uploader. `.env.docker.example` documents the same queue default. A focused Docker contract test guards these settings. Production still requires the existing Google Drive credentials/connection and Pharma runtime enablement.
