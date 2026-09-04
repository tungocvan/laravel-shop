# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-04
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Major/Clean Module Refactor — PR #154 MERGED / CLOSED**
- Current branch: `feat/muasamcong-kqlcnt-historical-recovery-export`
- Current delivery: **KQLCNT Historical Recovery, Canonical Award Warehouse & Four-Sheet Export**
- Delivery status: **IMPLEMENTED / LOCAL ACCEPTANCE IN PROGRESS**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Objective

Provide durable historical KQLCNT recovery when the upstream procurement source no longer exposes a TBMT/KQLCNT award list, preserve API-origin snapshots, normalize package/contract metadata for reporting, maintain one canonical award warehouse, and enable complete contractor-history export.

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
10. Canonical admin workspace at `/admin/muasamcong/kqlcnt-awards` with filters, selected/all export semantics and record editing.
11. Medicine metadata normalized in the canonical warehouse, including packaging specification, shelf life and registration/import-license identifier.
12. Package/contract metadata normalized on both KQLCNT snapshots and canonical award rows:
   - `contract_period`;
   - `contract_period_unit`;
   - `contract_period_text`;
   - `effect_frame_period`.
13. KQLCNT API resolution now exposes package execution metadata immediately; stored snapshots backfill the normalized values from `tbmt_raw` / `contracts_raw` when normalized columns are absent.
14. `KqlcntRecord` persistence also normalizes the four package/contract fields during save, so all record-writing paths converge on the same normalized snapshot contract.
15. Canonical persistence propagates package metadata to `KqlcntAwardItem` idempotently.
16. Four-sheet Excel export:
   - `Tong_quan_KQLCNT` includes execution period and effectiveness metadata;
   - `Danh_muc_trung_thau` contains normalized award/medicine details;
   - `Hop_dong` includes contract effectiveness period;
   - `Nha_thau_trung` contains winner aggregation.
17. Export scope invariant:
   - non-empty TBMT selection => selected TBMT only;
   - empty selection => all TBMT in the stored ContractorSearch scope.
18. New recovery workspace linked from contractor-history detail.
19. Recovery write/read-batch endpoints require both `view_muasamcong` and `muasamcong.pricing.sync`; recovery view/export remain under the normal view permission.

## Canonical architecture

`docs/modules/Muasamcong/MODULE.md` has been updated in this branch because persistence ownership and the KQLCNT recovery boundary changed.

Canonical flow:

```text
KQLCNT API -----------┐
Smart Pricing --------┤
Manual verification --┼--> Normalize / merge --> Canonical Award Warehouse --> Admin / reporting / export
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

## Files/boundaries added or changed

- migrations for recovery metadata, import batches, canonical award items, medicine fields and package-period fields;
- `KqlcntImportBatch`, `KqlcntAwardItem`, `KqlcntRecord` casts/normalization;
- `KqlcntService` API/stored package metadata normalization;
- `KqlcntHistoricalImportService`;
- `ContractorAwardCatalogService`, `ContractorAwardPersistenceService`, `ContractorAwardEnrichmentService`;
- `ContractorKqlcntExportService` and multi-sheet export classes;
- `ContractorKqlcntRecoveryController`, `KqlcntAwardController`;
- recovery and canonical admin views;
- contractor-history navigation;
- Muasamcong web routes;
- focused service/recovery/persistence/export/route tests;
- module contract and this handoff.

## Local verification reported by user

| Gate | Status | Evidence |
|---|---|---|
| Recovery migrations | PASS | Earlier recovery migrations applied successfully |
| Earlier focused historical recovery test | PASS | 4 tests, 15 assertions, 4.05s |
| Earlier Muasamcong regression | PASS | 42 tests, 385 assertions, 13.59s |
| Latest KQLCNT metadata focused tests | PENDING | Run after final pull |
| Canonical persistence focused tests | PENDING after latest metadata assertions | Run after final pull |
| Four-sheet export focused test | PENDING | Run after final pull |
| Recovery route authorization test | PENDING after latest authorization hardening | Run after final pull |
| Changed-file Pint | PENDING | Run after final pull |
| Route verification | PENDING | Run after final pull |
| Frontend build | PENDING | Run after final pull |
| Recovery UI smoke | PENDING | User manual acceptance |
| Canonical KQLCNT UI smoke | PENDING | User manual acceptance |
| Export workbook smoke | PENDING | User manual acceptance |
| Full project regression | NOT APPLICABLE | Module-scoped change; no shared/core contract changed |

## Manual UI acceptance checklist

On `/admin/muasamcong/contractors/history/{id}`:

1. open **Phục hồi / Export KQLCNT**;
2. confirm stored TBMT list and source badges render;
3. confirm a synced package such as `IB2500317380` shows normalized execution duration (`730 ngày`) where surfaced;
4. upload an `.xlsx` file;
5. inspect/adjust mapping;
6. Preview and verify valid/duplicate/conflict/error counts;
7. Confirm import without overwrite, then verify persisted values;
8. test conflict overwrite only with explicit checkbox/action;
9. sync a complete TBMT into the canonical warehouse and verify resync does not duplicate rows;
10. open `/admin/muasamcong/kqlcnt-awards`, verify searchable TBMT/contractor/medicine filters, package metadata column and edit form;
11. export with selected TBMT(s) and verify only selected scope;
12. export with no selection and verify all stored history scope;
13. open Excel and verify all four sheet names; `Tong_quan_KQLCNT` contains execution duration/effectiveness and `Hop_dong` contains effectiveness period.

## Remaining gate before PR

After pulling the latest branch, run one combined validation round: focused KQLCNT service/persistence/export/recovery/authorization tests, Muasamcong regression, changed-file Pint, route listing and frontend build. Then perform recovery/canonical UI and workbook smoke once. If all pass, refresh this handoff with final evidence before PR creation.

## Deferred debt

- generic import-template download and richer saved mapping profiles;
- retention policy for confirmed/stale import batches and raw preview payloads;
- streaming/chunked workbook export for very large historical datasets;
- server-side/autocomplete scaling when canonical distinct filter option counts become large;
- broader decomposition of legacy contractor Livewire/controller boundaries;
- unrelated historical Pint debt.

## Merge status

Not ready to merge until the latest focused tests, Pint, route/build gates and UI/export smoke are accepted. No PR has been created yet.
