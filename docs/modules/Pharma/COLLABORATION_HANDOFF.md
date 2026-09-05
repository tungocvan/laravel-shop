# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Official Facility Import — XLSX/CSV to canonical Partner master**
- Branch: `feat/pharma-official-facility-import`
- Base: `main` at `1f77f1575050648c143d45339d0ec8535e9dba6e`
- Status: **IMPLEMENTATION COMPLETE — awaiting local focused/regression/UI verification**
- Date: 2026-09-05
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one PR**

## Approved architecture

`Partner` remains the sole canonical organization master for hospitals/healthcare facilities. Pharma does not create a Hospital/Facility master.

Pipeline:

`Official XLSX/CSV -> Upload -> Pharma staging -> Validate/Normalize -> Match/Dedupe -> Preview -> Checkbox selection -> Import selected only -> Partner + PartnerSourceReference`.

Upload/staging does not write Partner.

## Persistence / ownership changes

Partner-owned canonical changes:

- nullable `partners.province_code` added as a generic, source-independent province attribute;
- companies/suppliers are not required to populate it and existing rows remain `NULL`;
- new `partner_source_references` stores generic `(source, external_id)` official identities, source province/date, first/last observation and metadata;
- `(source, external_id)` is unique.

Pharma-owned staging/audit tables:

- `pharma_official_import_batches`;
- `pharma_official_import_rows`.

Source-specific fields such as `bhxh_id`, `moh_id` or BHXH province code are not added to `partners`.

## Import implementation

Services live under `Modules/Pharma/Services/OfficialFacilityImport/`:

- `OfficialFacilityParser` — XLSX/CSV only, maximum 10,000 rows;
- `OfficialFacilityNormalizer`;
- `OfficialFacilityValidator`;
- `OfficialFacilityMatcher`;
- `OfficialFacilityImportService` — upload/staging/classification;
- `OfficialFacilityConflictResolver`;
- `OfficialFacilityPartnerImporter`;
- `OfficialFacilityImportSummary`.

SHA-256 duplicate-file detection is a warning, not a hard block. Duplicate staged rows receive a skip outcome. Re-import of the same official identity must resolve to the same Partner and refresh source observation data.

## Matching contract

Priority:

1. source + external_id;
2. tax code;
3. normalized name + canonical province;
4. normalized name + normalized address.

Classifications:

- `NEW`;
- `EXACT`;
- `LIKELY_MATCH`;
- `CONFLICT`;
- `INVALID`.

No fuzzy/AI matching is used. `LIKELY_MATCH` and `CONFLICT` cannot be imported until an admin explicitly chooses link/create/skip.

## Partner write protection

New Partner defaults:

- `legal_type = hospital`;
- `partner_types = ['customer']`;
- `status = active`;
- `source = import`;
- canonical `province_code` from explicit import context.

For an existing Partner:

- name is not auto-renamed;
- phone/email/contact_person are not overwritten;
- address is fill-only when empty;
- tax code is fill-only when empty; a conflicting non-empty value blocks;
- canonical province is fill-only when empty; a conflicting non-empty value blocks;
- Partner/source-reference/row outcome are written transactionally per selected row.

## Admin UI / authorization

Workspace: `/admin/pharma/official-facilities/import`.

Dashboard now exposes **Cơ sở KCB chính thức** when the user has view permission.

Permissions:

- `view_pharma_official_facilities`;
- `import_pharma_official_facilities`;
- `resolve_pharma_official_facility_conflicts`.

Workspace includes upload, preview/filter, page-scoped checkbox selection, conflict resolution, selected-only import and batch history.

Pagination is bounded to `10/25/50/100`; no `All`. Header checkbox explicitly means current page. Selection is stored in staging, and saving one page preserves selections on other pages.

## Manifest / contract synchronization

`Modules/Pharma/config/module.php` now declares `Partner` as an explicit dependency and includes current allocation/contract + official-facility permissions/tables so the module manifest no longer reflects the old pre-allocation shape.

`docs/modules/Pharma/MODULE.md` documents Partner ownership, staging boundaries, permissions, deterministic matching, selected-only semantics, canonical/source province separation and deferred scope.

## Tests added

Focused tests were added for:

- normalization and canonical-province validation;
- selected-only Partner import;
- NEW Partner defaults;
- source-reference creation;
- idempotent same-source re-import;
- preservation of manual Partner contact/name data.

These tests have been committed but have **not yet been claimed PASS** in this handoff. Local verification is the next gate.

## Required local verification

Run focused checks first. If they pass, run Pharma + Partner directly impacted regression, route inspection and build. Manual UI acceptance remains a separate `UI PASS` gate.

Do not claim merge readiness until local evidence is supplied.

## Explicitly out of scope

- MOH/BHXH runtime API auto-sync;
- captcha bypass;
- runtime scraping;
- PDF import;
- whole-province automatic import;
- fuzzy/AI facility matching;
- automatic merge of likely/conflict rows;
- source-specific IDs on `partners`;
- arbitrary overwrite of manual Partner data;
- delivery/inventory/invoice changes.

## Prior checkpoint

Drug Award Allocation & Hospital Contract Management was merged to `main` via PR #165 before this branch started. Partner remains the canonical hospital organization master established by that objective.
