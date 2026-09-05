# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Drug Bid Awards — Major Analysis & Synchronization with Muasamcong Canonical KQLCNT + HSSP Medicine Master standardization**
- Branch: `feat/pharma-multi-source-drug-intelligence`
- Status: **PHP GATE PASS — UI/build acceptance pending**
- Date: 2026-09-05
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **single implementation branch / single PR planned**

## Accepted architecture

The approved design is **Option B+ — Pharma Multi-source Medicine Intelligence Architecture**.

Canonical ownership is separated into three layers:

1. **Procurement Canonical — Muasamcong owner**: `muasamcong_kqlcnt_award_items` and external acquisition/recovery state.
2. **Medicine Master Canonical — Pharma/HSSP owner**: `pharma_medicines` + `pharma_medicine_sources`.
3. **Drug Award Business Canonical — Pharma owner**: `pharma_drug_bid_awards` + `pharma_drug_bid_award_sources`.

HSSP describes the product. Drug Award describes the procurement result. They link by `medicine_id` but remain separate business entities.

## Core invariant

**VALID RECORD != COMPLETE RECORD.**

Pharma Drug Award tolerates partial source data. Missing medicine attributes may be enriched from a deterministically linked HSSP Medicine profile for read/export purposes, but enrichment never overwrites a non-empty historical source value or misrepresents HSSP-derived data as procurement-origin data.

Procurement-only facts such as winning price, plan price, quantity, investor, contractor, decision and contract are never HSSP-enriched.

## Implemented schema

The following migrations are implemented and were successfully applied locally:

- `2026_09_05_010000_add_intelligence_fields_to_medicines_table.php`
- `2026_09_05_011000_create_medicine_sources_table.php`
- `2026_09_05_012000_add_intelligence_fields_to_drug_bid_awards_table.php`
- `2026_09_05_013000_create_drug_bid_award_sources_table.php`
- `2026_09_05_014000_relax_legacy_drug_award_constraints.php`

Migration result reported: **all five DONE**.

Key changes include Medicine/profile identity state, source-lineage tables, richer KQLCNT award fields, decimal quantity/pricing support, contract metadata, source provenance and relaxation of legacy constraints that blocked partial-source records.

## Implemented domain/services

### Medicine Master

- `Medicine` exposes identity/profile status, source lineage and award relations.
- `MedicineSource` records source-system lineage.
- `MedicineIdentityResolver` performs deterministic identity resolution; fuzzy auto-merge is not enabled.
- `MedicineService` supports expanded product search, quality filtering and source/award counts.
- Manual HSSP persistence supports incomplete records without fake placeholders.
- Existing Excel Medicine import validation remains source-specific and intentionally strict.

### Drug Award Business Catalog

- `DrugBidAward` carries procurement, medicine snapshot, pricing, party, contract, match-state and provenance fields.
- `DrugBidAwardSource` records many-source-to-one-business-record lineage.
- `DrugAwardProjectionData` is the source-neutral projection DTO.
- `DrugAwardProjectionService` provides idempotent multi-source projection, null-safe source updates and provisional Medicine creation when deterministic signals are strong enough.
- `effectiveMedicineAttribute()` exposes `award`, `hssp`, or `missing` provenance.
- Source `registration_or_import_license` remains on the award and is not blindly copied to HSSP `registration_number`.

### Muasamcong integration

Pharma owns the explicit adapter boundary:

- `Integrations/Muasamcong/MuasamcongKqlcntAwardAdapter.php`
- `Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php`

The adapter consumes `KqlcntAwardItem` without any dependency on Muasamcong UI/controller.

The web-triggered sync is intentionally bounded:

- default batch: **250** source rows;
- hard service cap: **1000**;
- continuation cursor: last Muasamcong source ID;
- action permission: `edit_pharma`;
- unavailable Muasamcong canonical table -> sync fails gracefully while existing Pharma data remains usable.

## Implemented workspaces

### `/admin/pharma/hssp`

The HSSP workspace is standardized as **Medicine Master / Product Profile / Data Quality**:

- search: name, ingredient, registration, concentration, manufacturer, country;
- filters: profile quality, circular group, special-control state;
- standard bounded pagination `10/25/50/100`;
- table exposes registration/identity, ingredient/strength, dosage/route, manufacturer/country, profile quality, source count and award count;
- selected/all export payload carries `profile_status` and selected IDs.

### `/admin/pharma/drug-bid-awards`

The Drug Award workspace is standardized as **Multi-source Procurement Award Intelligence**:

- expanded search: medicine, ingredient, medicine code, TBMT, lot, decision;
- filters: investor, contractor, source system, HSSP match status;
- source lineage displayed when available;
- effective medicine values can display **Bổ sung từ HSSP** while historical award snapshots remain untouched;
- match state shown as verified/provisional/ambiguous/unresolved;
- bounded KQLCNT sync action exposed to `edit_pharma` users;
- selected/all export contract preserved.

## Legacy-schema compatibility

List/export behavior checks whether `pharma_drug_bid_award_sources` exists before eager-loading/filtering source lineage.

This allows focused SQLite tests and older schemas to continue using legacy `source_type`, while migrated runtime schemas use the full lineage model.

## Export behavior

`DrugBidAwardImportExport` now:

- exports selected IDs when selection is non-empty;
- otherwise exports all records matching search/investor/company/source/match-status filters;
- uses source lineage for source filtering when the lineage table exists;
- exports effective medicine values with provenance plus richer procurement/business fields;
- does **not** include raw Muasamcong recovery payloads.

The old Excel import mapping remains intentionally strict/source-specific for compatibility.

## Verification evidence

Latest local verification reported by the user:

```text
Pint gate for latest affected files: PASS
PharmaDrugBidAwardWorkspaceTest: 7 passed, 56 assertions
Full tests/Feature/Pharma: 47 passed, 265 assertions
```

Earlier intelligence verification also included successful migration execution and focused projection coverage.

The PHP gate is therefore **PASS**.

## Remaining acceptance gates before PR

Run after pulling the latest documentation commits:

```bash
git pull --ff-only origin feat/pharma-multi-source-drug-intelligence
npm run build
```

Then manually smoke:

1. `/admin/pharma/hssp`
2. `/admin/pharma/drug-bid-awards`
3. KQLCNT sync button as an `edit_pharma` admin
4. selected-row export
5. no-selection filtered export
6. pagination `10/25/50/100`

### HSSP acceptance

- input/select borders visible;
- profile-quality filter works;
- incomplete/needs-review badges are understandable;
- source and award counts render;
- pagination works and selection resets on context change;
- edit/create navigation remains intact.

### Drug Award acceptance

- sync button does not block ordinary browsing;
- sync reports processed/projected/failed and exposes continuation when more data exists;
- HSSP-enriched values are visibly marked and do not replace award source fields in persistence;
- match-status/source filters work;
- decimal quantity and nullable legacy fields display safely;
- selected/all export semantics remain correct;
- pagination is bounded and responsive.

## PR readiness rule

Do not open/merge the consolidated PR until:

- `npm run build` PASS;
- user reports **UI PASS** for HSSP and Drug Award workspaces, including sync/export smoke;
- final evidence is appended to this handoff.

## Prior completed checkpoint

The 2026-09-02 corrective Pharma refactor was previously merged as PR #150. Its Admin UI, pagination and selected/all export standards remain the baseline and are extended rather than discarded by this objective.
