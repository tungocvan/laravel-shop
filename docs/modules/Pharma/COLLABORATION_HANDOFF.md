# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Drug Bid Awards — Major Analysis & Synchronization with Muasamcong Canonical KQLCNT + HSSP Medicine Master standardization**
- Branch: `feat/pharma-multi-source-drug-intelligence`
- Status: **IMPLEMENTATION IN PROGRESS — local verification/UI acceptance pending**
- Date: 2026-09-05
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **single implementation branch / single PR planned**

## Accepted architecture

The approved design is **Option B+ — Pharma Multi-source Medicine Intelligence Architecture**.

Canonical ownership is separated into three layers:

1. **Procurement Canonical — Muasamcong owner**: `muasamcong_kqlcnt_award_items` and external acquisition/recovery state.
2. **Medicine Master Canonical — Pharma/HSSP owner**: `pharma_medicines` + `pharma_medicine_sources`.
3. **Drug Award Business Canonical — Pharma owner**: `pharma_drug_bid_awards` + `pharma_drug_bid_award_sources`.

HSSP describes the product. Drug Award describes the procurement result. They may link by `medicine_id` but are not merged into one entity.

## Core invariant

**VALID RECORD != COMPLETE RECORD.**

Pharma Drug Award must tolerate partial source data. Missing medicine attributes may be enriched from a deterministically linked HSSP Medicine profile, but enrichment must never overwrite a non-empty historical source value or misrepresent HSSP-derived data as procurement-origin data.

Procurement-only facts such as winning price, plan price, quantity, investor, contractor, decision and contract are never HSSP-enriched.

## Implemented schema

The following migrations are implemented and have been successfully applied locally by the user:

- `2026_09_05_010000_add_intelligence_fields_to_medicines_table.php`
- `2026_09_05_011000_create_medicine_sources_table.php`
- `2026_09_05_012000_add_intelligence_fields_to_drug_bid_awards_table.php`
- `2026_09_05_013000_create_drug_bid_award_sources_table.php`
- `2026_09_05_014000_relax_legacy_drug_award_constraints.php`

Migration execution result reported: **all five DONE**.

Key changes include medicine/profile identity state, source-lineage tables, richer KQLCNT award fields, decimal quantity/pricing support, contract metadata, source provenance and relaxation of legacy constraints that prevented partial-source records.

## Implemented domain/services

### Medicine Master

- `Medicine` now exposes identity/profile status, source lineage and award relations.
- `MedicineSource` records source-system lineage.
- `MedicineIdentityResolver` performs deterministic identity resolution; fuzzy auto-merge is not enabled.
- `MedicineService` supports expanded product search, quality filtering and source/award counts.
- manual HSSP profile persistence supports incomplete records without fake placeholders.
- existing Excel Medicine import validation remains source-specific and is intentionally not relaxed in this objective.

### Drug Award Business Catalog

- `DrugBidAward` now carries procurement, medicine snapshot, pricing, party, contract, match-state and provenance fields.
- `DrugBidAwardSource` records many-source-to-one-business-record lineage.
- `DrugAwardProjectionData` is the source-neutral projection DTO.
- `DrugAwardProjectionService` provides idempotent multi-source projection, null-safe source updates and provisional Medicine creation when deterministic signals are strong enough.
- `effectiveMedicineAttribute()` exposes read-layer provenance: `award`, `hssp`, or `missing`.
- source `registration_or_import_license` is retained on the award and is not blindly copied to HSSP `registration_number`.

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

The HSSP workspace is being standardized as **Medicine Master / Product Profile / Data Quality**:

- search: name, ingredient, registration, concentration, manufacturer, country;
- filters: profile quality, circular group, special-control state;
- standard bounded pagination `10/25/50/100`;
- table exposes registration/identity, ingredient/strength, dosage/route, manufacturer/country, profile quality, source count and award count;
- selected/all export payload carries `profile_status` and selected IDs.

### `/admin/pharma/drug-bid-awards`

The Drug Award workspace is being standardized as **Multi-source Procurement Award Intelligence**:

- expanded search: medicine, ingredient, medicine code, TBMT, lot, decision;
- filters: investor, contractor, source system, HSSP match status;
- source lineage eager-loaded;
- effective medicine values can display **Bổ sung từ HSSP** while historical award snapshots remain untouched;
- match state shown as verified/provisional/ambiguous/unresolved;
- bounded KQLCNT sync action exposed to `edit_pharma` users;
- selected/all export contract preserved.

## Export behavior

`DrugBidAwardImportExport` now:

- exports selected IDs when selection is non-empty;
- otherwise exports all records matching search/investor/company/source/match-status filters;
- uses source lineage for source filtering where applicable;
- exports effective medicine values with provenance plus richer procurement/business fields;
- does **not** include raw Muasamcong recovery payloads.

The old Excel import mapping remains intentionally strict/source-specific for compatibility.

## Verification evidence so far

Before the latest UI/sync batch:

- focused Pint for `DrugAwardProjectionService.php` + `MedicineIdentityResolver.php`: **PASS — 2 files** after applying canonical Pint formatting;
- Pharma regression: **44 passed / 242 assertions**;
- new multi-source projection test: previously **4 passed / 16 assertions**;
- all five intelligence migrations: **DONE** locally.

The latest HSSP/Drug Award UI + bounded sync + export/test updates have **not yet completed the final local gate**. Do not treat this handoff as PR-ready until the next verification cycle passes.

## Focused verification required next

Run after pulling the latest branch:

```bash
./vendor/bin/pint --test \
Modules/Pharma/Livewire/Medicine/Index.php \
Modules/Pharma/Livewire/DrugBidAward/Index.php \
Modules/Pharma/Services/DrugBidAwardService.php \
Modules/Pharma/Services/DrugBidAwardImportExport.php \
Modules/Pharma/Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php \
Modules/Pharma/Services/DrugAwardProjectionService.php \
Modules/Pharma/Services/MedicineIdentityResolver.php

php artisan test tests/Feature/Pharma
```

If PHP gate passes, run UI smoke for:

1. `/admin/pharma/hssp`
2. `/admin/pharma/drug-bid-awards`
3. KQLCNT sync button as an `edit_pharma` admin
4. selected-row export
5. no-selection filtered export
6. pagination 10/25/50/100

## UI acceptance checklist

### HSSP

- input/select borders visible;
- profile-quality filter works;
- incomplete/needs-review badges are understandable;
- source and award counts render;
- pagination works and selection resets on context change;
- edit/create navigation remains intact.

### Drug Awards

- sync button does not block ordinary browsing;
- sync reports processed/projected/failed and exposes continuation when more data exists;
- HSSP-enriched values are visibly marked and do not replace award source fields in persistence;
- match-status/source filters work;
- decimal quantity and nullable legacy fields display safely;
- selected/all export semantics remain correct;
- pagination is bounded and responsive.

## Remaining before PR

- resolve any Pint/test issues from the latest batch;
- update `ANALYSIS.md` and `INFORMATION.md` with the finalized runtime mapping/evidence;
- record final test/routes/build/UI evidence here;
- user confirms UI PASS;
- create one consolidated PR.

## Prior completed checkpoint

The 2026-09-02 corrective Pharma refactor was previously merged as PR #150. Its Admin UI, pagination and selected/all export standards remain the baseline and are extended rather than discarded by this objective.
