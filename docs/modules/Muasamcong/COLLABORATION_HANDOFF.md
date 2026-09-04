# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-04
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Major/Clean Module Refactor — PR #154 MERGED / CLOSED**
- Current branch: `feat/muasamcong-kqlcnt-historical-recovery-export`
- Current delivery: **KQLCNT Historical Recovery, Canonical Award Warehouse, Four-Sheet Export & KQLCNT Operations Dashboard**
- Delivery status: **IMPLEMENTED / LOCAL ACCEPTANCE PASSED / READY FOR PR REVIEW**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Objective

Provide durable historical KQLCNT recovery when the upstream procurement source no longer exposes a TBMT/KQLCNT award list, preserve API-origin snapshots, normalize package/contract metadata for reporting, maintain one canonical award warehouse, enable complete contractor-history export, and make canonical KQLCNT the primary operational focus of the Muasamcong admin dashboard.

## Implemented scope

1. Additive recovery persistence:
   - `muasamcong_kqlcnt_import_batches` for import audit, mapping, preview and confirmation lineage;
   - `muasamcong_kqlcnt_award_items` as the canonical normalized medicine/lot award warehouse;
   - recovery metadata on `muasamcong_kqlcnt_records` for `api / import / mixed` provenance and last import reference.
2. Historical Excel import using PhpSpreadsheet through the existing `maatwebsite/excel` dependency.
3. Automatic header mapping plus editable mapping UI.
4. Mandatory Preview before Confirm.
5. ContractorSearch/TBMT scope validation: imported TBMT must belong to the selected stored contractor history.
6. Deduplication/conflict classification using stable identity/fingerprint keys.
7. Explicit conflict overwrite only when the operator opts in.
8. API snapshots/contracts/verified lots remain intact when imported recovery data is added.
9. Canonical award persistence merges API/KQLCNT, Smart Pricing, manual verification and imported recovery without creating a second reporting table.
10. Canonical admin workspace at `/admin/muasamcong/kqlcnt-awards` with searchable TBMT, **Chủ đầu tư**, medicine and ingredient filters; selected/all export semantics; and record editing.
11. Medicine metadata normalized in the canonical warehouse, including packaging specification, shelf life and registration/import-license identifier.
12. Package/contract metadata normalized on both KQLCNT snapshots and canonical award rows:
   - `contract_period`;
   - `contract_period_unit`;
   - `contract_period_text`;
   - `effect_frame_period`.
13. KQLCNT API resolution exposes package execution metadata immediately; stored snapshots backfill normalized values from `tbmt_raw` / `contracts_raw` when normalized columns are absent.
14. `KqlcntRecord` persistence normalizes the four package/contract fields during save so all record-writing paths converge on the same snapshot contract.
15. Canonical persistence propagates package metadata to `KqlcntAwardItem` idempotently.
16. Four-sheet Excel export:
   - `Tong_quan_KQLCNT` includes execution period and effectiveness metadata;
   - `Danh_muc_trung_thau` contains normalized award/medicine details;
   - `Hop_dong` includes contract effectiveness period;
   - `Nha_thau_trung` contains winner aggregation.
17. Export scope invariant:
   - non-empty TBMT selection => selected TBMT only;
   - empty selection => all TBMT in the stored ContractorSearch scope.
18. Recovery workspace linked from contractor-history detail.
19. Recovery write/read-batch endpoints require both `view_muasamcong` and `muasamcong.pricing.sync`; recovery view/export remain under the normal view permission.
20. Recovery award-count deduplication now uses the same logical identity strategy as canonical persistence (`lot_no` → `medicine_code` → normalized medicine/ingredient/concentration) so one logical award is not double-counted across API, Smart Pricing, import or stored snapshots.
21. Muasamcong admin dashboard refactored into a **KQLCNT Operations Dashboard**:
   - primary KPIs come from active canonical KQLCNT rows only;
   - metrics include TBMT count, canonical award rows, contractors, investors, total award value, rolling 30-day value and latest canonical sync;
   - operational attention shows missing KQLCNT detail, snapshot-not-canonical, import/mixed follow-up and failed contractor jobs;
   - recent canonical KQLCNT rows are surfaced without raw payloads;
   - workflow navigation prioritizes Contractor Search → History/TBMT → Recovery → Canonical KQLCNT;
   - Smart Pricing, synced pricing, HSMT and Wishlist are secondary tools;
   - queue/session/configuration are reduced to compact operational status.
22. Dashboard and canonical metrics read persisted DB state only; dashboard loading does not call upstream procurement APIs.

## Canonical architecture

`docs/modules/Muasamcong/MODULE.md` has been updated in this branch because persistence ownership and the KQLCNT recovery/reporting boundary changed.

Canonical flow:

```text
KQLCNT API -----------┐
Smart Pricing --------┤
Manual verification --┼--> Normalize / merge --> Canonical Award Warehouse --> Admin / Dashboard / Reporting / Export
Historical Excel -----┘              |
                                      v
                              KQLCNT snapshot metadata
```

Key invariants:

- Import never bypasses Preview.
- Import is limited to TBMTs belonging to the selected ContractorSearch.
- API sync must not remove imported recovery rows.
- Conflict does not silently overwrite existing normalized award data.
- Canonical sync is idempotent and does not call the upstream API.
- Curated/imported non-empty values are preserved over automatic/catalog values where the merge contract requires it.
- `contractPeriod` + `contractPeriodUnit` represent execution duration; `effectFramePeriod` is a separate effectiveness/frame period.
- `D / M / Y` display normalization is `ngày / tháng / năm`.
- Export reads persisted data; it does not call the upstream KQLCNT API.
- Selected/all checkbox semantics remain the module standard.
- Dashboard KQLCNT KPIs count only active canonical rows with `synced_from_catalog_at`; physical source rows are not summed independently.
- Recovery award counts and canonical persistence use compatible logical award identity rules to avoid multi-source double counting.

## Files/boundaries added or changed

- migrations for recovery metadata, import batches, canonical award items, medicine fields and package-period fields;
- `KqlcntImportBatch`, `KqlcntAwardItem`, `KqlcntRecord` casts/normalization;
- `KqlcntService` API/stored package metadata normalization;
- `KqlcntHistoricalImportService`;
- `ContractorAwardCatalogService`, `ContractorAwardPersistenceService`, `ContractorAwardEnrichmentService`;
- `ContractorKqlcntExportService` and multi-sheet export classes;
- `ContractorKqlcntRecoveryController`, `KqlcntAwardController`;
- `MuasamcongDashboardService` KQLCNT-centric metrics/attention summaries;
- recovery, canonical admin and dashboard views;
- contractor-history navigation;
- Muasamcong web routes;
- focused service/recovery/persistence/export/route/dashboard tests;
- module contract and this handoff.

## Local verification reported by user

| Gate | Status | Evidence |
|---|---|---|
| Recovery migrations | PASS | Recovery migrations applied successfully |
| Historical recovery / canonical focused validation | PASS | Latest broad module validation: **55 tests, 533 assertions** |
| Dashboard focused test | PASS | **4 tests PASS** after immutable rolling-window fixture fix |
| Changed-file Pint for dashboard scope | PASS | `MuasamcongDashboardService.php` + `MuasamcongDashboardTest.php` |
| KQLCNT service/persistence/export focused tests | PASS | Included in latest accepted module validation |
| Recovery route authorization | PASS | Included in latest accepted module validation |
| Route verification | PASS | Canonical award routes and module routes verified |
| Frontend build | PASS | Vite production build completed successfully |
| Dashboard UI smoke | PASS | User accepted redesigned `/admin/muasamcong/dashboard` |
| Recovery UI smoke | PASS | Recovery workflow and package-period display accepted during implementation |
| Canonical KQLCNT UI smoke | PASS | Canonical list/filter/edit workflow accepted during implementation |
| Recovery award-count regression | PASS by user UI verification cycle | Multi-source logical award count fix delivered before dashboard closeout |
| Full project regression | NOT APPLICABLE | Module-scoped change; no shared/core contract changed |

Notes:

- A broad Muasamcong Pint run can still surface unrelated pre-existing style debt outside the files changed by this delivery. That debt is intentionally not folded into this feature branch.
- No GitHub Actions workflow is currently attached to this branch; acceptance evidence above is from the user's local environment.

## Final manual acceptance highlights

Accepted behaviors include:

1. `/admin/muasamcong/contractors/history/{id}/kqlcnt-recovery` exposes stored KQLCNT source/status and normalized execution/effectiveness metadata.
2. Historical Excel recovery supports mapping, Preview, Confirm and conflict handling within the selected ContractorSearch/TBMT scope.
3. Recovery award counts deduplicate one logical award represented by multiple physical sources.
4. `/admin/muasamcong/kqlcnt-awards` filters by TBMT, **Chủ đầu tư**, medicine and other canonical fields; edit/export remain available.
5. Canonical export keeps both contractor and investor data even though the primary admin filter is investor-oriented.
6. `/admin/muasamcong/dashboard` now makes KQLCNT canonical data the primary operational workspace and provides direct access to Contractor Search, History/Recovery and Canonical KQLCNT.
7. Dashboard KPI queries do not double-count source tables and do not load raw payload data.

## Deferred debt

- generic import-template download and richer saved mapping profiles;
- retention policy for confirmed/stale import batches and raw preview payloads;
- streaming/chunked workbook export for very large historical datasets;
- server-side/autocomplete scaling when canonical distinct filter option counts become large;
- richer KQLCNT analytics (time-series value, top contractor, top investor, medicine/ingredient statistics and price history) once data volume is sufficient;
- broader decomposition of legacy contractor Livewire/controller boundaries;
- unrelated historical Pint debt.

## Merge status

**READY FOR PR REVIEW.**

The branch is ready to open against `main`. User has accepted the latest focused tests and Dashboard UI. Production enablement/deployment remains outside this delivery and must not be changed by the PR.
