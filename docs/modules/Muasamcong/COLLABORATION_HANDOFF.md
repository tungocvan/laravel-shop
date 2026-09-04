# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-04
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Major/Clean Module Refactor — PR #154 MERGED / CLOSED**
- Current branch: `feat/muasamcong-kqlcnt-historical-recovery-export`
- Current delivery: **KQLCNT Historical Recovery & Four-Sheet Export**
- Delivery status: **IMPLEMENTED / LOCAL ACCEPTANCE IN PROGRESS**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Objective

Provide durable historical KQLCNT recovery when the upstream procurement source no longer exposes a TBMT/KQLCNT award list, while preserving API-origin snapshots and enabling complete contractor-history export.

## Implemented scope

1. Additive recovery persistence:
   - `muasamcong_kqlcnt_import_batches` for import audit, mapping, preview and confirmation lineage;
   - `muasamcong_kqlcnt_award_items` for normalized medicine/lot award rows;
   - recovery metadata on `muasamcong_kqlcnt_records` for `api / import / mixed` provenance and last import reference.
2. Historical Excel import using PhpSpreadsheet through the existing `maatwebsite/excel` dependency.
3. Automatic header mapping plus editable mapping UI.
4. Mandatory Preview before Confirm.
5. ContractorSearch/TBMT scope validation: imported TBMT must belong to the selected stored contractor history.
6. Deduplication/conflict classification using stable identity/fingerprint keys.
7. Explicit conflict overwrite only when the operator opts in.
8. API snapshots/contracts/verified lots remain intact when imported recovery data is added.
9. Four-sheet Excel export:
   - `Tong_quan_KQLCNT`
   - `Danh_muc_trung_thau`
   - `Hop_dong`
   - `Nha_thau_trung`
10. Export scope invariant:
   - non-empty TBMT selection => selected TBMT only;
   - empty selection => all TBMT in the stored ContractorSearch scope.
11. New recovery workspace linked from contractor-history detail.
12. Recovery write/read-batch endpoints require both `view_muasamcong` and `muasamcong.pricing.sync`; recovery view/export remain under the normal view permission.

## Canonical architecture

`docs/modules/Muasamcong/MODULE.md` has been updated in this branch because persistence ownership and the KQLCNT recovery boundary changed.

Canonical flow:

```text
KQLCNT API ----------------------┐
                                v
                    Muasamcong KQLCNT persistence
                                ^
Historical Excel -> Map -> Preview -> Confirm
                                |
                                v
                    Four-sheet contractor export
```

Key invariants:

- Import never bypasses Preview.
- Import is limited to TBMTs belonging to the selected ContractorSearch.
- API sync must not remove imported recovery rows.
- Conflict does not silently overwrite existing normalized award data.
- Export reads persisted data; it does not call the upstream KQLCNT API.
- Selected/all checkbox semantics remain the module standard.

## Files/boundaries added or changed

- migrations for recovery metadata, import batches and award items;
- `KqlcntImportBatch`, `KqlcntAwardItem`, `KqlcntRecord` casts;
- `KqlcntHistoricalImportService`;
- `ContractorKqlcntExportService` and multi-sheet export classes;
- `ContractorKqlcntRecoveryController`;
- `contractor-kqlcnt-recovery.blade.php`;
- contractor-history navigation;
- Muasamcong web routes;
- focused recovery and route authorization tests;
- module contract and this handoff.

## Local verification reported by user

| Gate | Status | Evidence |
|---|---|---|
| Recovery migrations | PASS | 3 migrations applied successfully |
| Focused historical recovery test | PASS | 4 tests, 15 assertions, 4.05s |
| Muasamcong regression | PASS | 42 tests, 385 assertions, 13.59s |
| Recovery route authorization test | PENDING after latest authorization hardening | Run after pull |
| Changed-file Pint | PENDING | Run after pull |
| Route verification | PENDING | Run after pull |
| Frontend build | PENDING | Run after pull |
| Recovery UI smoke | PENDING | User manual acceptance |
| Export workbook smoke | PENDING | User manual acceptance |
| Full project regression | NOT APPLICABLE | Module-scoped change; no shared/core contract changed |

## Manual UI acceptance checklist

On `/admin/muasamcong/contractors/history/{id}`:

1. open **Phục hồi / Export KQLCNT**;
2. confirm stored TBMT list and source badges render;
3. upload an `.xlsx` file;
4. inspect/adjust mapping;
5. Preview and verify valid/duplicate/conflict/error counts;
6. Confirm import without overwrite, then verify persisted values;
7. test conflict overwrite only with explicit checkbox/action;
8. export with selected TBMT(s) and verify only selected scope;
9. export with no selection and verify all stored history scope;
10. open Excel and verify all four sheet names and representative values/numeric columns.

## Remaining gate before PR

After pulling the latest branch, run the focused recovery + route authorization tests, Muasamcong regression, changed-file Pint, route listing and frontend build. Then perform manual UI/export smoke. If all pass, refresh this handoff with final evidence before PR creation.

## Deferred debt

- generic import-template download and richer saved mapping profiles;
- retention policy for confirmed/stale import batches and raw preview payloads;
- streaming/chunked workbook export for very large historical datasets;
- broader decomposition of legacy contractor Livewire/controller boundaries;
- unrelated historical Pint debt.

## Merge status

Not ready to merge until the latest authorization hardening and UI/export smoke are accepted. No PR has been created yet.
